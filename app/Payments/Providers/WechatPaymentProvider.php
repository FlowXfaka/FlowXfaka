<?php

namespace App\Payments\Providers;

use App\Jobs\SyncOrderPaymentStatus;
use App\Models\Order;
use App\Models\PaymentChannel;
use App\Payments\Contracts\PaymentProvider;
use App\Services\OrderPaymentService;
use App\Services\OrderPaymentStatusSyncThrottle;
use App\Services\WechatPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WechatPaymentProvider implements PaymentProvider
{
    public function __construct(
        private readonly WechatPayService $wechat,
        private readonly OrderPaymentService $payments,
        private readonly OrderPaymentStatusSyncThrottle $syncThrottle,
    ) {
    }

    public function key(): string
    {
        return 'wechat';
    }

    public function label(): string
    {
        return "\u{5FAE}\u{4FE1}\u{652F}\u{4ED8}";
    }

    public function supportsChannel(PaymentChannel $channel): bool
    {
        return trim((string) $channel->provider) === $this->key();
    }

    public function isChannelEnabled(PaymentChannel $channel): bool
    {
        return $this->supportsChannel($channel)
            && (bool) $channel->is_enabled
            && $this->wechat->isConfigured($channel);
    }

    public function canStart(Order $order): bool
    {
        $channel = $this->wechat->channelForOrder($order);

        return $channel instanceof PaymentChannel
            && $this->isChannelEnabled($channel)
            && $order->status === OrderPaymentService::STATUS_PENDING
            && (float) $order->amount >= 0.01;
    }

    public function start(Order $order, Request $request): Response|RedirectResponse
    {
        $order->loadMissing('product');
        $channel = $this->wechat->channelForOrder($order);

        if (! $channel || ! $this->isChannelEnabled($channel)) {
            return $this->redirectToOrder($order, "\u{5F53}\u{524D}\u{672A}\u{542F}\u{7528}\u{53EF}\u{7528}\u{7684}\u{5FAE}\u{4FE1}\u{652F}\u{4ED8}\u{901A}\u{9053}\u{3002}");
        }

        if (! $order->product || ! $order->product->is_active) {
            return $this->redirectToOrder($order, "\u{5F53}\u{524D}\u{8BA2}\u{5355}\u{5173}\u{8054}\u{5546}\u{54C1}\u{4E0D}\u{53EF}\u{7528}\u{3002}");
        }

        if (in_array($order->status, [OrderPaymentService::STATUS_PAID, OrderPaymentService::STATUS_DELIVERED], true)) {
            return $this->redirectToOrder($order, "\u{5F53}\u{524D}\u{8BA2}\u{5355}\u{5DF2}\u{5B8C}\u{6210}\u{652F}\u{4ED8}\u{FF0C}\u{65E0}\u{9700}\u{91CD}\u{590D}\u{53D1}\u{8D77}\u{3002}");
        }

        if ((float) $order->amount < 0.01) {
            return $this->redirectToOrder($order, "\u{5F53}\u{524D}\u{8BA2}\u{5355}\u{91D1}\u{989D}\u{5F02}\u{5E38}\u{FF0C}\u{6682}\u{65F6}\u{65E0}\u{6CD5}\u{652F}\u{4ED8}\u{3002}");
        }

        try {
            $payload = is_array($order->payment_payload) ? $order->payment_payload : [];
            $qrCode = trim((string) ($payload['qr_code'] ?? ''));

            if ($qrCode === '') {
                $nativeOrder = $this->wechat->createNative($order, $channel);
                $payload['qr_code'] = $nativeOrder['qr_code'];
                $payload['native_response'] = $nativeOrder['payload'];
                $payload['native_created_at'] = now('Asia/Shanghai')->format('Y-m-d H:i:s');

                $order->payment_channel = $this->key();
                $order->payment_payload = $payload;
                $order->save();
            }

            return $this->redirectToOrder($order, "\u{8BF7}\u{4F7F}\u{7528}\u{5FAE}\u{4FE1}\u{626B}\u{7801}\u{652F}\u{4ED8}\u{3002}");
        } catch (\Throwable $exception) {
            report($exception);

            return $this->redirectToOrder($order, "\u{652F}\u{4ED8}\u{7801}\u{751F}\u{6210}\u{5931}\u{8D25}\u{FF0C}\u{8BF7}\u{7A0D}\u{540E}\u{91CD}\u{8BD5}\u{3002}");
        }
    }

    public function handleNotify(Request $request): Response
    {
        $channelId = (int) $request->query('channel');
        $channel = $channelId > 0
            ? PaymentChannel::query()->whereKey($channelId)->where('provider', $this->key())->first()
            : null;

        $notification = $this->wechat->parseNotification($request, $channel);

        if (! is_array($notification)) {
            return $this->failureResponse(400);
        }

        $resource = $notification['resource'] ?? null;
        if (! is_array($resource)) {
            return $this->failureResponse(422);
        }

        $orderNo = trim((string) ($resource['out_trade_no'] ?? ''));

        if ($orderNo === '') {
            return $this->failureResponse(422);
        }

        $order = Order::query()->where('order_no', $orderNo)->first();

        if (! $order) {
            return $this->failureResponse(404);
        }

        $channel = $channel ?? $this->wechat->channelForOrder($order);
        if (! $channel || ! $this->wechat->isConfigured($channel)) {
            return $this->failureResponse(400);
        }

        $tradeState = trim((string) ($resource['trade_state'] ?? ''));
        if ($tradeState !== 'SUCCESS') {
            return $this->successResponse();
        }

        $paidAmount = (int) ($resource['amount']['total'] ?? 0);
        $expectedAmount = (int) round(((float) $order->amount) * 100);
        $appId = trim((string) ($resource['appid'] ?? ''));
        $mchId = trim((string) ($resource['mchid'] ?? ''));

        if ($paidAmount !== $expectedAmount
            || $appId !== $this->wechat->appId($channel)
            || $mchId !== $this->wechat->mchId($channel)) {
            return $this->failureResponse(422);
        }

        $this->payments->markProviderPaid(
            $order,
            $resource,
            'notify',
            $this->key(),
            tradeNoKey: 'transaction_id',
            buyerLogonKey: null,
        );

        return $this->successResponse();
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $orderNo = trim((string) ($request->query('out_trade_no') ?? ''));

        if ($orderNo === '') {
            return redirect()->route('home');
        }

        return redirect()
            ->route('orders.show', ['order' => $orderNo])
            ->with('order_notice', "\u{8BA2}\u{5355}\u{72B6}\u{6001}\u{6B63}\u{5728}\u{540C}\u{6B65}\u{FF0C}\u{8BF7}\u{7A0D}\u{540E}\u{5237}\u{65B0}\u{67E5}\u{770B}\u{3002}");
    }

    public function supportsStatusSync(): bool
    {
        return true;
    }

    public function dispatchStatusSync(Order $order): void
    {
        SyncOrderPaymentStatus::dispatch((int) $order->getKey());
    }

    public function syncPendingOrder(Order $order): void
    {
        if ($order->status !== OrderPaymentService::STATUS_PENDING) {
            return;
        }

        $channel = $this->wechat->channelForOrder($order);
        if (! $channel || ! $this->wechat->isConfigured($channel)) {
            return;
        }

        if (! $this->syncThrottle->acquire($order, $this->key())) {
            return;
        }

        try {
            $query = $this->wechat->queryTrade($order, $channel);
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        $tradeState = trim((string) ($query['trade_state'] ?? ''));
        $paidAmount = (int) ($query['amount']['total'] ?? 0);
        $expectedAmount = (int) round(((float) $order->amount) * 100);

        if ($tradeState !== 'SUCCESS' || $paidAmount !== $expectedAmount) {
            return;
        }

        $this->payments->markProviderPaid(
            $order,
            $query,
            'query',
            $this->key(),
            tradeNoKey: 'transaction_id',
            buyerLogonKey: null,
        );
    }

    public function callbackUrls(): array
    {
        return [
            'notify' => route('payments.notify', ['provider' => $this->key()]),
            'return' => route('payments.return', ['provider' => $this->key()]),
        ];
    }

    public function defaultRoutePath(): string
    {
        return '/payments/wechat/start';
    }

    private function redirectToOrder(Order $order, string $notice): RedirectResponse
    {
        return redirect()
            ->route('orders.show', ['order' => $order->order_no])
            ->with('order_notice', $notice);
    }

    private function successResponse(): Response
    {
        return response()->noContent();
    }

    private function failureResponse(int $status = 400): Response
    {
        return response('failure', $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
