<?php

namespace App\Payments\Providers;

use App\Jobs\SyncOrderPaymentStatus;
use App\Models\Order;
use App\Models\PaymentChannel;
use App\Payments\Contracts\PaymentProvider;
use App\Services\AlipayService;
use App\Services\OrderPaymentService;
use App\Services\OrderPaymentStatusSyncThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlipayPaymentProvider implements PaymentProvider
{
    public function __construct(
        private readonly AlipayService $alipay,
        private readonly OrderPaymentService $payments,
        private readonly OrderPaymentStatusSyncThrottle $syncThrottle,
    ) {
    }

    public function key(): string
    {
        return 'alipay';
    }

    public function label(): string
    {
        return "\u{652F}\u{4ED8}\u{5B9D}";
    }

    public function supportsChannel(PaymentChannel $channel): bool
    {
        return trim((string) $channel->provider) === $this->key();
    }

    public function isChannelEnabled(PaymentChannel $channel): bool
    {
        return $this->supportsChannel($channel)
            && (bool) $channel->is_enabled
            && $this->alipay->isConfigured($channel);
    }

    public function canStart(Order $order): bool
    {
        $channel = $this->alipay->channelForOrder($order);

        return $channel instanceof PaymentChannel
            && $this->isChannelEnabled($channel)
            && $order->status === OrderPaymentService::STATUS_PENDING
            && (float) $order->amount >= 0.01;
    }

    public function start(Order $order, Request $request): Response|RedirectResponse
    {
        $order->loadMissing('product');
        $channel = $this->alipay->channelForOrder($order);

        if (! $channel || ! $this->isChannelEnabled($channel)) {
            return $this->redirectToOrder($order, "\u{5F53}\u{524D}\u{672A}\u{542F}\u{7528}\u{53EF}\u{7528}\u{7684}\u{652F}\u{4ED8}\u{5B9D}\u{901A}\u{9053}\u{3002}");
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

        if ($this->alipay->usesScanMode($channel)) {
            try {
                $payload = is_array($order->payment_payload) ? $order->payment_payload : [];
                $qrCode = trim((string) ($payload['qr_code'] ?? ''));

                if ($qrCode === '') {
                    $precreate = $this->alipay->createPrecreate($order, $channel);
                    $payload['qr_code'] = $precreate['qr_code'];
                    $payload['precreate_response'] = $precreate['payload'];
                    $payload['precreate_at'] = now('Asia/Shanghai')->format('Y-m-d H:i:s');

                    $order->payment_channel = $this->key();
                    $order->payment_payload = $payload;
                    $order->save();
                }

                return $this->redirectToOrder($order, "\u{8BF7}\u{4F7F}\u{7528}\u{652F}\u{4ED8}\u{5B9D}\u{626B}\u{7801}\u{652F}\u{4ED8}\u{3002}");
            } catch (\Throwable $exception) {
                report($exception);

                return $this->redirectToOrder($order, "\u{652F}\u{4ED8}\u{7801}\u{751F}\u{6210}\u{5931}\u{8D25}\u{FF0C}\u{8BF7}\u{7A0D}\u{540E}\u{91CD}\u{8BD5}\u{3002}");
            }
        }

        return response(
            $this->alipay->buildPayHtml($order, $this->isMobileRequest($request), $channel),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    public function handleNotify(Request $request): Response
    {
        $payload = $request->post();
        $orderNo = trim((string) ($payload['out_trade_no'] ?? ''));

        if ($orderNo === '') {
            return $this->failureResponse(422);
        }

        $order = Order::query()->where('order_no', $orderNo)->first();
        if (! $order) {
            return $this->failureResponse(404);
        }

        $channel = $this->alipay->channelForOrder($order);
        if (! $channel || ! $this->alipay->verify($payload, $channel)) {
            return $this->failureResponse(400);
        }

        $tradeStatus = trim((string) ($payload['trade_status'] ?? ''));
        if (! in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            return $this->successResponse();
        }

        $appId = trim((string) ($payload['app_id'] ?? ''));
        $paidAmount = number_format((float) ($payload['total_amount'] ?? 0), 2, '.', '');
        $expectedAmount = number_format((float) $order->amount, 2, '.', '');

        if ($appId !== $this->alipay->appId($channel) || $paidAmount !== $expectedAmount) {
            return $this->failureResponse(422);
        }

        $this->payments->markAlipayPaid($order, $payload, 'notify');

        return $this->successResponse();
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $orderNo = trim((string) ($request->query('out_trade_no') ?? ''));

        if ($orderNo === '') {
            return redirect()->route('home');
        }

        $notice = "\u{652F}\u{4ED8}\u{7ED3}\u{679C}\u{6B63}\u{5728}\u{540C}\u{6B65}\u{FF0C}\u{8BF7}\u{7A0D}\u{540E}\u{5237}\u{65B0}\u{67E5}\u{770B}\u{3002}";
        $order = Order::query()->where('order_no', $orderNo)->first();
        $channel = $order ? $this->alipay->channelForOrder($order) : null;

        if ($order && $channel && $request->query('sign') && $this->alipay->verify($request->query(), $channel)) {
            if ($order->status === OrderPaymentService::STATUS_PENDING) {
                $this->syncPendingOrder($order);
                $order = Order::query()->whereKey($order->id)->first() ?? $order;
            }

            $notice = $order->status === OrderPaymentService::STATUS_PENDING
                ? "\u{652F}\u{4ED8}\u{8BF7}\u{6C42}\u{5DF2}\u{5B8C}\u{6210}\u{FF0C}\u{6B63}\u{5728}\u{540C}\u{6B65}\u{8BA2}\u{5355}\u{72B6}\u{6001}\u{3002}"
                : "\u{652F}\u{4ED8}\u{6210}\u{529F}\u{FF0C}\u{8BA2}\u{5355}\u{72B6}\u{6001}\u{5DF2}\u{66F4}\u{65B0}\u{3002}";
        }

        return redirect()
            ->route('orders.show', ['order' => $orderNo])
            ->with('order_notice', $notice);
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

        $channel = $this->alipay->channelForOrder($order);
        if (! $channel || ! $this->alipay->isConfigured($channel)) {
            return;
        }

        if (! $this->syncThrottle->acquire($order, $this->key())) {
            return;
        }

        try {
            $query = $this->alipay->queryTrade($order, $channel);
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        $tradeStatus = trim((string) ($query['trade_status'] ?? ''));
        $paidAmount = array_key_exists('total_amount', $query)
            ? number_format((float) ($query['total_amount'] ?? 0), 2, '.', '')
            : null;
        $expectedAmount = number_format((float) $order->amount, 2, '.', '');

        if (! in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            return;
        }

        if ($paidAmount !== null && $paidAmount !== $expectedAmount) {
            return;
        }

        $this->payments->markAlipayPaid($order, $query, 'query');
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
        return '/payments/alipay/start';
    }

    private function redirectToOrder(Order $order, string $notice): RedirectResponse
    {
        return redirect()
            ->route('orders.show', ['order' => $order->order_no])
            ->with('order_notice', $notice);
    }

    private function successResponse(): Response
    {
        return response('success', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function failureResponse(int $status = 400): Response
    {
        return response('failure', $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function isMobileRequest(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());
        foreach (['iphone', 'ipad', 'android', 'mobile', 'micromessenger'] as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
