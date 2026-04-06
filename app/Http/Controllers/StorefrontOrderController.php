<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\ProductCard;
use App\Models\SiteSetting;
use App\Payments\PaymentProviderRegistry;
use App\Services\OrderPaymentStatusSyncThrottle;
use App\Support\StorefrontProductResolver;
use App\Support\StorefrontTheme;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StorefrontOrderController extends Controller
{
    private const STATUS_PENDING = "\u{5F85}\u{652F}\u{4ED8}";
    private const STATUS_PAID = "\u{5DF2}\u{652F}\u{4ED8}";
    private const STATUS_DELIVERED = "\u{5DF2}\u{53D1}\u{8D27}";
    private const STATUS_PROCESSING = "\u{5904}\u{7406}\u{4E2D}";
    private const CARD_UNUSED = "\u{672A}\u{4F7F}\u{7528}";
    private const PICKUP_CODE_LENGTH = 6;
    private const PICKUP_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRTUVWXY';
    private const PICKUP_CODE_SESSION_KEY = 'storefront_pickup_codes';

    public function query(Request $request): View
    {
        $storefrontTheme = SiteSetting::current()->resolvedFrontendTheme();
        $orderNo = trim((string) $request->query('order_no'));
        $contact = trim((string) $request->query('contact'));
        $querySubmitted = $orderNo !== '' || $contact !== '';
        $isContactLookup = $orderNo === '' && $contact !== '';
        $lookupOrders = [];
        $lookupError = null;

        if ($querySubmitted) {
            if ($orderNo === '' && $contact === '') {
                $lookupError = "\u{8BF7}\u{8F93}\u{5165}\u{8BA2}\u{5355}\u{53F7}\u{6216}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{3002}";
            } else {
                $orders = Order::query()
                    ->with('product.category')
                    ->where('status', '!=', self::STATUS_PENDING)
                    ->when($orderNo !== '', fn ($query) => $query->where('order_no', $orderNo))
                    ->when($isContactLookup, fn ($query) => $query->where('contact', $contact))
                    ->orderByDesc('created_at')
                    ->limit($isContactLookup ? 20 : 10)
                    ->get();

                if ($orders->isNotEmpty()) {
                    $lookupOrders = $orders
                        ->map(fn (Order $order) => $this->mapOrder($order, [
                            'reveal_delivered_cards' => ! $isContactLookup,
                            'mask_order_no' => $isContactLookup,
                            'include_unlock_metadata' => $isContactLookup,
                        ]))
                        ->all();
                } else {
                    $lookupError = "\u{6CA1}\u{6709}\u{627E}\u{5230}\u{5BF9}\u{5E94}\u{8BA2}\u{5355}\u{FF0C}\u{8BF7}\u{68C0}\u{67E5}\u{540E}\u{91CD}\u{8BD5}\u{3002}";
                }
            }
        }

        return view(StorefrontTheme::view('order-query', $storefrontTheme), [
            'querySubmitted' => $querySubmitted,
            'lookupOrders' => $lookupOrders,
            'lookupError' => $lookupError,
            'orderNo' => $orderNo,
            'contact' => $contact,
            'isContactLookup' => $isContactLookup,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'contact' => ['required', 'string', 'max:120'],
            'pickup_code' => ['required', 'string', 'min:' . self::PICKUP_CODE_LENGTH, 'max:' . self::PICKUP_CODE_LENGTH],
        ], [
            'pickup_code.required' => "\u{8BF7}\u{8F93}\u{5165}\u{53D6}\u{8D27}\u{7801}\u{3002}",
            'pickup_code.min' => "\u{53D6}\u{8D27}\u{7801}\u{683C}\u{5F0F}\u{4E0D}\u{6B63}\u{786E}\u{3002}",
            'pickup_code.max' => "\u{53D6}\u{8D27}\u{7801}\u{683C}\u{5F0F}\u{4E0D}\u{6B63}\u{786E}\u{3002}",
        ]);

        $order = Order::query()
            ->whereKey((int) $data['order_id'])
            ->where('contact', trim((string) $data['contact']))
            ->where('status', '!=', self::STATUS_PENDING)
            ->first();

        if (! $order) {
            return back()
                ->withErrors(['pickup_code' => "\u{672A}\u{627E}\u{5230}\u{5BF9}\u{5E94}\u{8BA2}\u{5355}\u{FF0C}\u{8BF7}\u{68C0}\u{67E5}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{540E}\u{91CD}\u{8BD5}\u{3002}"])
                ->withInput();
        }

        $pickupCode = $this->normalizePickupCode((string) $data['pickup_code']);

        if (strlen($pickupCode) !== self::PICKUP_CODE_LENGTH) {
            return back()
                ->withErrors(['pickup_code' => "\u{53D6}\u{8D27}\u{7801}\u{683C}\u{5F0F}\u{4E0D}\u{6B63}\u{786E}\u{3002}"])
                ->withInput();
        }

        if (! filled($order->pickup_code_hash)) {
            return back()
                ->withErrors(['pickup_code' => "\u{8BE5}\u{8BA2}\u{5355}\u{672A}\u{914D}\u{7F6E}\u{53D6}\u{8D27}\u{7801}\u{FF0C}\u{8BF7}\u{4F7F}\u{7528}\u{8BA2}\u{5355}\u{53F7}\u{67E5}\u{8BE2}\u{3002}"])
                ->withInput();
        }

        if (! Hash::check($pickupCode, (string) $order->pickup_code_hash)) {
            return back()
                ->withErrors(['pickup_code' => "\u{53D6}\u{8D27}\u{7801}\u{4E0D}\u{6B63}\u{786E}\u{FF0C}\u{8BF7}\u{68C0}\u{67E5}\u{540E}\u{91CD}\u{8BD5}\u{3002}"])
                ->withInput();
        }

        $this->rememberPickupCode($request, $order, $pickupCode);

        return redirect()
            ->route('orders.show', ['order' => $order->order_no])
            ->with('order_notice', "\u{53D6}\u{8D27}\u{7801}\u{9A8C}\u{8BC1}\u{6210}\u{529F}\u{FF0C}\u{5DF2}\u{4E3A}\u{60A8}\u{6253}\u{5F00}\u{8BA2}\u{5355}\u{8BE6}\u{60C5}\u{3002}");
    }

    public function store(Request $request, Product $product, PaymentProviderRegistry $providers, StorefrontProductResolver $resolver): RedirectResponse
    {
        $product = $resolver->resolve($product);
        $availableStock = ProductCard::query()
            ->where('product_id', $product->id)
            ->where('status', self::CARD_UNUSED)
            ->count();

        if ($availableStock < 1) {
            return back()
                ->withErrors(['quantity' => "\u{5F53}\u{524D}\u{5546}\u{54C1}\u{5E93}\u{5B58}\u{4E0D}\u{8DB3}\u{3002}"])
                ->withInput();
        }

        if ((float) $product->price < 0.01) {
            return back()
                ->withErrors(['quantity' => "\u{5F53}\u{524D}\u{5546}\u{54C1}\u{4EF7}\u{683C}\u{5F02}\u{5E38}\u{FF0C}\u{6682}\u{65F6}\u{65E0}\u{6CD5}\u{652F}\u{4ED8}\u{3002}"])
                ->withInput();
        }

        $data = $request->validate([
            'contact' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'payment_channel' => ['required', 'integer'],
        ], [
            'contact.required' => "\u{8BF7}\u{586B}\u{5199}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{3002}",
            'contact.string' => "\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{683C}\u{5F0F}\u{4E0D}\u{6B63}\u{786E}\u{3002}",
            'contact.max' => "\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{957F}\u{5EA6}\u{4E0D}\u{80FD}\u{8D85}\u{8FC7} 120 \u{4E2A}\u{5B57}\u{7B26}\u{3002}",
            'quantity.required' => "\u{8BF7}\u{9009}\u{62E9}\u{8D2D}\u{4E70}\u{6570}\u{91CF}\u{3002}",
            'quantity.integer' => "\u{8D2D}\u{4E70}\u{6570}\u{91CF}\u{683C}\u{5F0F}\u{4E0D}\u{6B63}\u{786E}\u{3002}",
            'quantity.min' => "\u{8D2D}\u{4E70}\u{6570}\u{91CF}\u{81F3}\u{5C11}\u{4E3A} 1\u{3002}",
            'quantity.max' => "\u{8D2D}\u{4E70}\u{6570}\u{91CF}\u{8D85}\u{51FA}\u{9650}\u{5236}\u{3002}",
            'payment_channel.required' => "\u{8BF7}\u{9009}\u{62E9}\u{4ED8}\u{6B3E}\u{65B9}\u{5F0F}\u{3002}",
            'payment_channel.integer' => "\u{4ED8}\u{6B3E}\u{65B9}\u{5F0F}\u{65E0}\u{6548}\u{3002}",
        ]);

        $quantity = (int) $data['quantity'];

        if ($quantity > $availableStock) {
            return back()
                ->withErrors(['quantity' => "\u{8D2D}\u{4E70}\u{6570}\u{91CF}\u{8D85}\u{8FC7}\u{5F53}\u{524D}\u{5E93}\u{5B58}\u{3002}"])
                ->withInput();
        }

        $selectedChannel = PaymentChannel::query()
            ->whereKey((int) $data['payment_channel'])
            ->where('is_enabled', true)
            ->first();

        if (! $selectedChannel) {
            return back()
                ->withErrors(['payment_channel' => "\u{8BF7}\u{9009}\u{62E9}\u{53EF}\u{7528}\u{7684}\u{4ED8}\u{6B3E}\u{65B9}\u{5F0F}\u{3002}"])
                ->withInput();
        }

        $selectedProvider = trim((string) $selectedChannel->provider);
        $provider = $providers->forChannel($selectedChannel);

        if (! $provider || ! $provider->isChannelEnabled($selectedChannel)) {
            return back()
                ->withErrors(['payment_channel' => "\u{5F53}\u{524D}\u{4ED8}\u{6B3E}\u{65B9}\u{5F0F}\u{6682}\u{4E0D}\u{53EF}\u{7528}\u{3002}"])
                ->withInput();
        }

        $pickupCode = $this->generatePickupCode();
        $order = Order::query()->create([
            'order_no' => $this->makeOrderNo(),
            'product_id' => $product->id,
            'contact' => trim((string) $data['contact']),
            'pickup_code_hash' => Hash::make($pickupCode),
            'pickup_code_encrypted' => Crypt::encryptString($pickupCode),
            'quantity' => $quantity,
            'amount' => number_format((float) $product->price * $quantity, 2, '.', ''),
            'status' => self::STATUS_PENDING,
            'payment_channel' => $selectedProvider,
            'payment_payload' => [
                'selected_channel_id' => $selectedChannel->id,
                'selected_channel_name' => trim((string) $selectedChannel->name),
                'selected_provider' => $selectedProvider,
            ],
            'delivered_cards' => [],
        ]);

        $this->rememberPickupCode($request, $order, $pickupCode);

        return redirect()
            ->route('orders.show', ['order' => $order->order_no]);
    }

    public function show(Request $request, Order $order, PaymentProviderRegistry $providers): View
    {
        $storefrontTheme = SiteSetting::current()->resolvedFrontendTheme();
        $order->loadMissing('product.category');
        $provider = $providers->forOrder($order);
        $pickupCode = $this->resolveRememberedPickupCode($request, $order);

        if ($order->status === self::STATUS_PENDING && $provider?->supportsStatusSync()) {
            $provider->dispatchStatusSync($order);
        }

        $canPay = $provider !== null
            && $provider->canStart($order)
            && $order->status === self::STATUS_PENDING
            && (float) $order->amount >= 0.01;

        return view(StorefrontTheme::view('order-show', $storefrontTheme), [
            'orderData' => $this->mapOrder($order),
            'paymentEnabled' => $provider !== null && $provider->canStart($order),
            'paymentUrl' => $canPay ? route('payments.start', ['order' => $order->order_no]) : null,
            'expiresAtMs' => $this->resolvePaymentExpiryTimestampMs($order, $provider?->key()),
            'pickupCode' => $pickupCode,
            'pickupCodeDisplay' => $pickupCode !== null ? $this->formatPickupCode($pickupCode) : '',
        ]);
    }

    public function status(Order $order, PaymentProviderRegistry $providers, OrderPaymentStatusSyncThrottle $syncThrottle): JsonResponse
    {
        $order->loadMissing('product.category');
        $pollAfterMs = null;
        $provider = $providers->forOrder($order);

        if ($order->status === self::STATUS_PENDING && $provider?->supportsStatusSync()) {
            $provider->syncPendingOrder($order);
            $pollAfterMs = $syncThrottle->nextPollDelayMs($order, $provider->key());
        }

        $freshOrder = Order::query()->with('product.category')->whereKey($order->id)->first() ?? $order;
        $orderData = $this->mapOrder($freshOrder);

        return response()->json([
            'code' => 200,
            'status' => $orderData['status'],
            'redirect' => $orderData['status'] !== self::STATUS_PENDING
                ? route('orders.show', ['order' => $freshOrder->order_no, 'refresh' => time()])
                : null,
            'payment_qr_code' => (string) ($orderData['payment_qr_code'] ?? ''),
            'delivered_cards' => $orderData['delivered_cards'],
            'poll_after_ms' => $orderData['status'] === self::STATUS_PENDING ? $pollAfterMs : null,
        ]);
    }

    private function makeOrderNo(): string
    {
        do {
            $orderNo = 'OD' . now('Asia/Shanghai')->format('ymdHis') . Str::upper(Str::random(4));
        } while (Order::query()->where('order_no', $orderNo)->exists());

        return $orderNo;
    }

    private function formatBeijingTime($value): ?string
    {
        return $value ? $value->copy()->timezone('Asia/Shanghai')->format('Y-m-d H:i:s') : null;
    }

    private function mapOrder(Order $order, array $options = []): array
    {
        $revealDeliveredCards = (bool) ($options['reveal_delivered_cards'] ?? true);
        $maskOrderNo = (bool) ($options['mask_order_no'] ?? false);
        $includeUnlockMetadata = (bool) ($options['include_unlock_metadata'] ?? false);
        $statusText = match ((string) $order->status) {
            self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_DELIVERED => (string) $order->status,
            default => self::STATUS_PROCESSING,
        };

        $statusClass = match ($statusText) {
            self::STATUS_DELIVERED => 'status-chip--success',
            self::STATUS_PAID => 'status-chip--info',
            self::STATUS_PENDING => 'status-chip--warning',
            default => 'status-chip--muted',
        };

        $productImage = $order->product?->image_path ?: 'product-placeholder.svg';
        $deliveredCards = collect($order->delivered_cards ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->values()
            ->all();
        $paymentPayload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $paymentChannelName = trim((string) ($paymentPayload['selected_channel_name'] ?? ''));
        if ($paymentChannelName === '') {
            $paymentChannelName = match ((string) $order->payment_channel) {
                'alipay' => "\u{652F}\u{4ED8}\u{5B9D}",
                'wxpay', 'wechat', 'wechatpay' => "\u{5FAE}\u{4FE1}\u{652F}\u{4ED8}",
                default => "\u{652F}\u{4ED8}\u{65B9}\u{5F0F}",
            };
        }

        return [
            'id' => $order->id,
            'order_no' => $maskOrderNo ? $this->maskOrderNo((string) $order->order_no) : $order->order_no,
            'contact' => $order->contact,
            'quantity' => (int) $order->quantity,
            'amount' => number_format((float) $order->amount, 2, '.', ''),
            'status' => $statusText,
            'status_class' => $statusClass,
            'created_at' => $this->formatBeijingTime($order->created_at),
            'paid_at' => $this->formatBeijingTime($order->paid_at),
            'delivered_at' => $this->formatBeijingTime($order->delivered_at),
            'product_name' => $order->product?->name,
            'product_sku' => $order->product?->sku,
            'product_image' => $productImage,
            'product_category_name' => $order->product?->category?->name,
            'payment_channel_label' => $paymentChannelName,
            'payment_qr_code' => $paymentPayload['qr_code'] ?? '',
            'delivered_cards' => $revealDeliveredCards ? $deliveredCards : [],
            'pickup_code_enabled' => filled($order->pickup_code_hash),
            'unlock_order_id' => $includeUnlockMetadata ? $order->id : null,
        ];
    }

    private function generatePickupCode(): string
    {
        $alphabet = self::PICKUP_CODE_ALPHABET;
        $maxIndex = strlen($alphabet) - 1;
        $pickupCode = '';

        for ($index = 0; $index < self::PICKUP_CODE_LENGTH; $index++) {
            $pickupCode .= $alphabet[random_int(0, $maxIndex)];
        }

        return $pickupCode;
    }

    private function normalizePickupCode(string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $value));
    }

    private function formatPickupCode(string $value): string
    {
        return $this->normalizePickupCode($value);
    }

    private function maskOrderNo(string $value): string
    {
        $orderNo = trim($value);

        if ($orderNo === '') {
            return '****';
        }

        return '****' . substr($orderNo, -4);
    }

    private function rememberPickupCode(Request $request, Order $order, string $pickupCode): void
    {
        $pickupCodes = $request->session()->get(self::PICKUP_CODE_SESSION_KEY, []);

        if (! is_array($pickupCodes)) {
            $pickupCodes = [];
        }

        $pickupCodes[(string) $order->id] = $this->normalizePickupCode($pickupCode);
        $request->session()->put(self::PICKUP_CODE_SESSION_KEY, $pickupCodes);
    }

    private function resolveRememberedPickupCode(Request $request, Order $order): ?string
    {
        $pickupCodes = $request->session()->get(self::PICKUP_CODE_SESSION_KEY, []);

        if (! is_array($pickupCodes)) {
            return null;
        }

        $pickupCode = $this->normalizePickupCode((string) ($pickupCodes[(string) $order->id] ?? ''));

        return strlen($pickupCode) === self::PICKUP_CODE_LENGTH ? $pickupCode : null;
    }

    private function resolvePaymentExpiryTimestampMs(Order $order, ?string $providerKey): int
    {
        $createdAt = $order->created_at
            ? $order->created_at->copy()->timezone('Asia/Shanghai')
            : now('Asia/Shanghai');

        return $createdAt
            ->addSeconds($this->resolvePaymentTimeoutSeconds($providerKey))
            ->getTimestampMs();
    }

    private function resolvePaymentTimeoutSeconds(?string $providerKey): int
    {
        return match (trim((string) $providerKey)) {
            'alipay' => $this->parseAlipayTimeoutExpress((string) config('payments.alipay.timeout_express', '15m')),
            'wechat' => max(60, (int) config('payments.wechat.timeout_minutes', 15) * 60),
            default => 15 * 60,
        };
    }

    private function parseAlipayTimeoutExpress(string $value): int
    {
        $timeout = trim(strtolower($value));

        if ($timeout === '') {
            return 15 * 60;
        }

        if (preg_match('/^(\d+)\s*([smhd])$/', $timeout, $matches) !== 1) {
            return 15 * 60;
        }

        $amount = max(1, (int) ($matches[1] ?? 0));
        $unit = $matches[2] ?? 'm';

        $seconds = match ($unit) {
            's' => $amount,
            'm' => $amount * 60,
            'h' => $amount * 3600,
            'd' => $amount * 86400,
            default => 15 * 60,
        };

        return max(60, $seconds);
    }
}
