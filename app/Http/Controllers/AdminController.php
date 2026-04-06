<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\ProductCard;
use App\Models\ProductCategory;
use App\Payments\PaymentProviderRegistry;
use App\Services\CatalogService;
use App\Services\EditorImageUploadService;
use App\Services\OrderFulfillmentService;
use App\Services\OrderPaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    private const CARD_UNUSED = "\u{672A}\u{4F7F}\u{7528}";

    public function overview(Request $request): View
    {
        $settings = \App\Models\SiteSetting::current();
        $lowStockThreshold = max(0, (int) ($settings->low_stock_threshold ?? 10));
        $paidStatuses = ["\u{5df2}\u{652f}\u{4ed8}", "\u{5df2}\u{53d1}\u{8d27}"];
        $overviewPeriods = [
            'today' => "\u{4eca}\u{5929}",
            'yesterday' => "\u{6628}\u{5929}",
            'week' => "\u{4e00}\u{5468}\u{5185}",
            'month' => "\u{4e00}\u{6708}\u{5185}",
            'all' => "\u{5168}\u{90e8}",
            'custom' => "\u{81ea}\u{5b9a}\u{4e49}\u{65f6}\u{95f4}",
        ];

        $activePeriod = $request->string('period')->toString();
        if (! array_key_exists($activePeriod, $overviewPeriods)) {
            $activePeriod = 'today';
        }

        $customStart = $request->string('start_at')->toString();
        $customEnd = $request->string('end_at')->toString();
        $customStartAt = null;
        $customEndAt = null;

        if ($customStart !== '') {
            try {
                $customStartAt = \Illuminate\Support\Carbon::parse($customStart, config('app.timezone'));
            } catch (\Throwable $exception) {
                $customStart = '';
            }
        }

        if ($customEnd !== '') {
            try {
                $customEndAt = \Illuminate\Support\Carbon::parse($customEnd, config('app.timezone'));
            } catch (\Throwable $exception) {
                $customEnd = '';
            }
        }

        if ($customStartAt && $customEndAt && $customEndAt->lt($customStartAt)) {
            [$customStartAt, $customEndAt] = [$customEndAt, $customStartAt];
            [$customStart, $customEnd] = [
                $customStartAt->format('Y-m-d\TH:i'),
                $customEndAt->format('Y-m-d\TH:i'),
            ];
        }

        $filteredOrders = Order::query();
        $now = now()->timezone(config('app.timezone'));
        $rangeStart = null;
        $rangeEnd = null;

        switch ($activePeriod) {
            case 'today':
                $rangeStart = $now->copy()->startOfDay();
                $rangeEnd = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $rangeStart = $now->copy()->subDay()->startOfDay();
                $rangeEnd = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $rangeStart = $now->copy()->subDays(6)->startOfDay();
                $rangeEnd = $now->copy()->endOfDay();
                break;
            case 'month':
                $rangeStart = $now->copy()->subDays(29)->startOfDay();
                $rangeEnd = $now->copy()->endOfDay();
                break;
            case 'custom':
                $rangeStart = $customStartAt;
                $rangeEnd = $customEndAt;
                break;
        }

        if ($rangeStart) {
            $filteredOrders->where('created_at', '>=', $rangeStart);
        }

        if ($rangeEnd) {
            $filteredOrders->where('created_at', '<=', $rangeEnd);
        }

        $productStats = Product::query()
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as on_sale_products',
                ["\u{4e0a}\u{67b6}\u{4e2d}"],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as off_sale_products',
                ["\u{5df2}\u{4e0b}\u{67b6}"],
            )
            ->first();

        $orderStats = (clone $filteredOrders)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as paid_orders',
                $paidStatuses,
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as unpaid_orders',
                ["\u{5f85}\u{652f}\u{4ed8}"],
            )
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status IN (?, ?) THEN amount ELSE 0 END), 0) as paid_amount',
                $paidStatuses,
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as unpaid_amount',
                ["\u{5f85}\u{652f}\u{4ed8}"],
            )
            ->first();

        $totalProducts = (int) ($productStats?->total_products ?? 0);
        $onSaleProducts = (int) ($productStats?->on_sale_products ?? 0);
        $offSaleProducts = (int) ($productStats?->off_sale_products ?? 0);

        $totalOrders = (int) ($orderStats?->total_orders ?? 0);
        $paidOrders = (int) ($orderStats?->paid_orders ?? 0);
        $unpaidOrders = (int) ($orderStats?->unpaid_orders ?? 0);

        $totalAmount = (float) ($orderStats?->total_amount ?? 0);
        $paidAmount = (float) ($orderStats?->paid_amount ?? 0);
        $unpaidAmount = (float) ($orderStats?->unpaid_amount ?? 0);

        $recentSales = (clone $filteredOrders)
            ->whereIn('status', $paidStatuses)
            ->with(['product.category'])
            ->orderByRaw('COALESCE(paid_at, delivered_at, created_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (Order $order): array {
                $saleTime = $order->paid_at ?? $order->delivered_at ?? $order->created_at;

                return [
                    'time' => $saleTime ? $saleTime->timezone(config('app.timezone'))->format('Y-m-d H:i:s') : '--',
                    'product_name' => trim((string) ($order->product?->name ?? '商品已删除')),
                    'category_name' => trim((string) ($order->product?->category?->name ?? '未分类')),
                    'quantity' => (int) $order->quantity,
                    'amount' => number_format((float) $order->amount, 2, '.', ''),
                    'order_no' => trim((string) $order->order_no),
                    'contact' => trim((string) $order->contact),
                ];
            });

        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->where('status', "\u{4e0a}\u{67b6}\u{4e2d}")
            ->withCount(['cards as available_cards_count' => function ($query): void {
                $query->where('status', "\u{672a}\u{4f7f}\u{7528}");
            }])
            ->having('available_cards_count', '<', $lowStockThreshold)
            ->orderBy('available_cards_count')
            ->orderBy('id')
            ->get(['id', 'name']);

        return view('admin.overview', [
            'title' => "\u{6982}\u{89c8}",
            'stats' => [
                ['label' => "\u{603b}\u{5546}\u{54c1}", 'value' => (string) $totalProducts],
                ['label' => "\u{5728}\u{552e}\u{4e2d}", 'value' => (string) $onSaleProducts],
                ['label' => "\u{5df2}\u{4e0b}\u{67b6}", 'value' => (string) $offSaleProducts],
                ['label' => "\u{603b}\u{8ba2}\u{5355}", 'value' => (string) $totalOrders],
                ['label' => "\u{5df2}\u{652f}\u{4ed8}\u{8ba2}\u{5355}", 'value' => (string) $paidOrders],
                ['label' => "\u{672a}\u{652f}\u{4ed8}\u{8ba2}\u{5355}", 'value' => (string) $unpaidOrders],
                ['label' => "\u{603b}\u{91d1}\u{989d}", 'value' => "\u{00A5}" . number_format($totalAmount, 2)],
                ['label' => "\u{5df2}\u{652f}\u{4ed8}\u{91d1}\u{989d}", 'value' => "\u{00A5}" . number_format($paidAmount, 2)],
                ['label' => "\u{672a}\u{652f}\u{4ed8}\u{91d1}\u{989d}", 'value' => "\u{00A5}" . number_format($unpaidAmount, 2)],
            ],
            'overviewPeriods' => $overviewPeriods,
            'activePeriod' => $activePeriod,
            'customStart' => $customStartAt?->format('Y-m-d\TH:i') ?? '',
            'customEnd' => $customEndAt?->format('Y-m-d\TH:i') ?? '',
            'recentSales' => $recentSales,
            'lowStockProducts' => $lowStockProducts,
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }

    public function updateLowStockThreshold(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:99999'],
            'period' => ['nullable', 'string', Rule::in(['today', 'yesterday', 'week', 'month', 'all', 'custom'])],
            'start_at' => ['nullable', 'string', 'max:32'],
            'end_at' => ['nullable', 'string', 'max:32'],
        ]);

        \App\Models\SiteSetting::current()->update([
            'low_stock_threshold' => (int) $data['low_stock_threshold'],
        ]);

        $parameters = [];
        if (! empty($data['period'])) {
            $parameters['period'] = $data['period'];
        }
        if (($data['period'] ?? null) === 'custom') {
            if (! empty($data['start_at'])) {
                $parameters['start_at'] = $data['start_at'];
            }
            if (! empty($data['end_at'])) {
                $parameters['end_at'] = $data['end_at'];
            }
        }

        return redirect()->route('admin.overview', $parameters);
    }



    public function products(Request $request, CatalogService $catalogService): View
    {
        $listing = $catalogService->adminProductIndex($request->query('category'));

        return view('admin.products', [
            'title' => "\u{5546}\u{54C1}\u{7BA1}\u{7406}",
            'subtitle' => "\u{6309}\u{5206}\u{7C7B}\u{7B5B}\u{9009}\u{5546}\u{54C1}\u{FF0C}\u{5E76}\u{53EF}\u{76F4}\u{63A5}\u{8FDB}\u{884C}\u{65B0}\u{589E}\u{5206}\u{7C7B}\u{3001}\u{7F16}\u{8F91}\u{3001}\u{5220}\u{9664}\u{548C}\u{5361}\u{5BC6}\u{7BA1}\u{7406}\u{3002}",
            'categories' => $listing['categories'],
            'products' => $listing['products'],
            'selectedCategorySlug' => $listing['selected_category_slug'],
            'selectedCategoryName' => $listing['selected_category_name'],
            'totalProducts' => $listing['total_products'],
        ]);
    }

    public function storeCategory(Request $request, CatalogService $catalogService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('product_categories', 'name')->where(fn ($query) => $query->where('is_active', true))],
            'return_to' => ['nullable', 'string', 'in:create,edit'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
        ]);

        $category = $catalogService->createCategory($data['name']);
        $returnTo = $data['return_to'] ?? null;
        $productId = $data['product_id'] ?? null;

        if ($returnTo === 'create') {
            return redirect()
                ->route('admin.products.create', ['category' => $category->slug])
                ->with('product_notice', "\u{5206}\u{7C7B}\u{5DF2}\u{65B0}\u{589E}\u{3002}");
        }

        if ($returnTo === 'edit' && $productId) {
            return redirect()
                ->route('admin.products.edit', ['product' => $productId, 'category' => $category->slug])
                ->with('product_notice', "\u{5206}\u{7C7B}\u{5DF2}\u{65B0}\u{589E}\u{3002}");
        }

        return redirect()
            ->route('admin.products', ['category' => $category->slug])
            ->with('product_notice', "\u{5206}\u{7C7B}\u{5DF2}\u{65B0}\u{589E}\u{3002}");
    }

    public function updateCategory(Request $request, ProductCategory $category, CatalogService $catalogService): RedirectResponse
    {
        $data = $request->validate([
            'rename_name' => ['required', 'string', 'max:30', Rule::unique('product_categories', 'name')->ignore($category->id)->where(fn ($query) => $query->where('is_active', true))],
        ]);

        $category = $catalogService->renameCategory($category, $data['rename_name']);

        return redirect()
            ->route('admin.products', ['category' => $category->slug])
            ->with('product_notice', "\u{5206}\u{7C7B}\u{5DF2}\u{66F4}\u{65B0}\u{3002}");
    }

    public function destroyCategory(ProductCategory $category, CatalogService $catalogService): RedirectResponse
    {
        if (! $catalogService->deleteCategory($category)) {
            return redirect()
                ->route('admin.products', ['category' => $category->slug])
                ->with('product_notice', "\u{5F53}\u{524D}\u{5206}\u{7C7B}\u{4E0B}\u{8FD8}\u{6709}\u{5546}\u{54C1}\u{FF0C}\u{65E0}\u{6CD5}\u{5220}\u{9664}\u{3002}");
        }

        return redirect()
            ->route('admin.products')
            ->with('product_notice', "\u{5206}\u{7C7B}\u{5DF2}\u{5220}\u{9664}\u{3002}");
    }

    public function createProduct(Request $request, CatalogService $catalogService): View
    {
        $categories = $catalogService->categoryOptions();
        $selectedCategoryId = $this->resolveEditorCategorySlug($categories, old('category_id', $request->query('category')));
        $selectedCategory = collect($categories)->firstWhere('id', $selectedCategoryId);

        return view('admin.products-create', [
            'title' => "\u{65B0}\u{589E}\u{5546}\u{54C1}",
            'subtitle' => "\u{5728}\u{5F53}\u{524D}\u{5206}\u{7C7B}\u{4E0B}\u{521B}\u{5EFA}\u{65B0}\u{7684}\u{5546}\u{54C1}\u{3002}",
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedCategoryName' => $selectedCategory['name'] ?? "\u{672A}\u{5206}\u{7C7B}",
            'mode' => 'create',
            'productRecord' => null,
            'returnCategory' => $selectedCategoryId,
        ]);
    }

    public function storeProduct(Request $request, CatalogService $catalogService): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'name' => ['required', 'string', 'max:80'],
            'compare_price' => ['nullable', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'price' => ['required', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'detail_tag_style' => ['nullable', 'string', Rule::in(['glass', 'minimal', 'gradient'])],
            'detail_tags' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:3072'],
        ], $this->productValidationMessages());

        if (
            ($data['compare_price'] ?? null) !== null
            && $data['compare_price'] !== ''
            && (float) $data['compare_price'] < (float) $data['price']
        ) {
            throw ValidationException::withMessages([
                'compare_price' => '划线价不能小于售价。',
            ]);
        }

        $catalogService->createProduct($data);

        return redirect()
            ->route('admin.products', ['category' => $data['category_id']])
            ->with('product_notice', "\u{5546}\u{54C1}\u{5DF2}\u{65B0}\u{589E}\u{3002}");
    }

    public function toggleProductStatus(Request $request, Product $product, CatalogService $catalogService): RedirectResponse
    {
        $product->loadMissing('category');
        $categorySlug = $request->string('category')->toString() ?: ($product->category?->slug ?? null);
        $catalogService->toggleProductStatus($product);

        return redirect()
            ->route('admin.products', ['category' => $categorySlug])
            ->with('product_notice', "\u{5546}\u{54C1}\u{72B6}\u{6001}\u{5DF2}\u{66F4}\u{65B0}\u{3002}");
    }

    public function editProduct(Product $product, Request $request, CatalogService $catalogService): View
    {
        $product->loadMissing('category');
        $categories = $catalogService->categoryOptions();
        $selectedCategoryId = $this->resolveEditorCategorySlug($categories, old('category_id', $request->query('category', $product->category?->slug)));
        $selectedCategory = collect($categories)->firstWhere('id', $selectedCategoryId);

        return view('admin.products-create', [
            'title' => "\u{7F16}\u{8F91}\u{5546}\u{54C1}",
            'subtitle' => "\u{4FEE}\u{6539}\u{5546}\u{54C1}\u{57FA}\u{7840}\u{4FE1}\u{606F}\u{5E76}\u{4FDD}\u{5B58}\u{3002}",
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedCategoryName' => $selectedCategory['name'] ?? "\u{672A}\u{5206}\u{7C7B}",
            'mode' => 'edit',
            'productRecord' => $product,
            'returnCategory' => $request->query('category', $selectedCategoryId),
        ]);
    }

    public function updateProduct(Request $request, Product $product, CatalogService $catalogService): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'name' => ['required', 'string', 'max:80'],
            'compare_price' => ['nullable', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'price' => ['required', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'detail_tag_style' => ['nullable', 'string', Rule::in(['glass', 'minimal', 'gradient'])],
            'detail_tags' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
        ], $this->productValidationMessages());

        if (
            ($data['compare_price'] ?? null) !== null
            && $data['compare_price'] !== ''
            && (float) $data['compare_price'] < (float) $data['price']
        ) {
            throw ValidationException::withMessages([
                'compare_price' => '划线价不能小于售价。',
            ]);
        }

        $catalogService->updateProduct($product, $data);

        return redirect()
            ->route('admin.products', ['category' => $data['category_id']])
            ->with('product_notice', "\u{5546}\u{54C1}\u{5DF2}\u{66F4}\u{65B0}\u{3002}");
    }

    public function uploadEditorImage(Request $request, EditorImageUploadService $uploader): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:5120'],
        ]);

        return response()->json([
            'url' => $uploader->store($data['image'], 'uploads/editor', 'editor'),
        ]);
    }

    public function destroyProduct(Request $request, Product $product, CatalogService $catalogService): RedirectResponse
    {
        $product->loadMissing('category');
        $categorySlug = $request->string('category')->toString() ?: ($product->category?->slug ?? null);
        DB::transaction(function () use ($catalogService, $product): void {
            ProductCard::query()
                ->where('product_id', $product->id)
                ->delete();

            $catalogService->deactivateProduct($product);
        });

        return redirect()
            ->route('admin.products', ['category' => $categorySlug])
            ->with('product_notice', "\u{5546}\u{54C1}\u{5DF2}\u{5220}\u{9664}\u{3002}");
    }

    public function reorderCategories(Request $request, CatalogService $catalogService): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        if (! $catalogService->reorderCategories($data['ids'])) {
            return response()->json([
                'message' => "\u{5206}\u{7C7B}\u{6570}\u{636E}\u{4E0D}\u{5339}\u{914D}\u{FF0C}\u{65E0}\u{6CD5}\u{4FDD}\u{5B58}\u{3002}",
            ], 422);
        }

        return response()->json([
            'message' => "\u{5206}\u{7C7B}\u{987A}\u{5E8F}\u{5DF2}\u{4FDD}\u{5B58}\u{3002}",
        ]);
    }

    public function reorderProducts(Request $request, CatalogService $catalogService): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'string'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        if (! $catalogService->reorderProducts($data['category_id'], $data['ids'])) {
            return response()->json([
                'message' => "\u{5546}\u{54C1}\u{6570}\u{636E}\u{4E0D}\u{5339}\u{914D}\u{FF0C}\u{65E0}\u{6CD5}\u{4FDD}\u{5B58}\u{3002}",
            ], 422);
        }

        return response()->json([
            'message' => "\u{5546}\u{54C1}\u{987A}\u{5E8F}\u{5DF2}\u{4FDD}\u{5B58}\u{3002}",
        ]);
    }

    public function cards(Request $request): View
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount('cards')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedCategory = $this->resolveSelectedCategory($categories, $request->query('category'));
        $products = collect();
        $selectedProduct = null;
        $cards = null;
        $deliveredCards = null;
        $availableCount = 0;
        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        if ($selectedCategory) {
            $products = Product::query()
                ->where('category_id', $selectedCategory->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'sku', 'card_dispatch_mode']);

            $selectedProduct = $products->firstWhere('id', (int) $request->query('product')) ?: $products->first();

            if ($selectedProduct) {
                $unusedQuery = ProductCard::query()
                    ->where('product_id', $selectedProduct->id)
                    ->where('status', "\u{672A}\u{4F7F}\u{7528}");

                $availableCount = (int) $unusedQuery->count();

                $cards = (clone $unusedQuery)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate($perPage)
                    ->withQueryString();

                $deliveredCards = ProductCard::query()
                    ->where('product_id', $selectedProduct->id)
                    ->where('status', '!=', "\u{672A}\u{4F7F}\u{7528}")
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate($perPage, ['*'], 'delivered_page')
                    ->withQueryString();

                $deliveredOrderNos = $deliveredCards->getCollection()
                    ->map(fn (ProductCard $card): ?string => $this->extractOrderNoFromCardNote((string) $card->note))
                    ->filter()
                    ->unique()
                    ->values();

                $deliveredOrders = $deliveredOrderNos->isEmpty()
                    ? collect()
                    : Order::query()
                        ->whereIn('order_no', $deliveredOrderNos)
                        ->get(['order_no', 'contact'])
                        ->keyBy('order_no');

                $deliveredCards = $deliveredCards->through(function (ProductCard $card) use ($deliveredOrders): ProductCard {
                    $orderNo = $this->extractOrderNoFromCardNote((string) $card->note);
                    $card->delivery_order_no = $orderNo;
                    $card->delivery_contact = $orderNo && $deliveredOrders->has($orderNo)
                        ? (string) $deliveredOrders->get($orderNo)->contact
                        : null;

                    return $card;
                });
            }
        }

        return view('admin.cards', [
            'title' => "\u{5361}\u{5BC6}\u{7BA1}\u{7406}",
            'subtitle' => "\u{6309}\u{5206}\u{7C7B}\u{67E5}\u{770B}\u{5546}\u{54C1}\u{5361}\u{5BC6}\u{FF0C}\u{652F}\u{6301}\u{5BFC}\u{5165}\u{3001}\u{7F16}\u{8F91}\u{3001}\u{5220}\u{9664}\u{FF0C}\u{5E76}\u{53EF}\u{67E5}\u{770B}\u{5DF2}\u{53D1}\u{8D27}\u{5361}\u{5BC6}\u{8BB0}\u{5F55}\u{3002}",
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'products' => $products,
            'cards' => $cards,
            'deliveredCards' => $deliveredCards,
            'selectedProduct' => $selectedProduct,
            'availableCount' => $availableCount,
            'currentCardDispatchMode' => Product::normalizeCardDispatchMode($selectedProduct?->card_dispatch_mode),
            'cardDispatchModeOptions' => Product::cardDispatchModeOptions(),
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function updateCardDispatchMode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'dispatch_mode' => ['required', 'string', Rule::in(Product::cardDispatchModes())],
            'page' => ['nullable', 'integer', 'min:1'],
            'delivered_page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);

        [$category, $product] = $this->resolveCardTargets($data['category'], (int) $data['product_id']);

        $product->update([
            'card_dispatch_mode' => Product::normalizeCardDispatchMode($data['dispatch_mode']),
        ]);

        return redirect()
            ->route('admin.cards', array_filter([
                'category' => $category->slug,
                'product' => $product->id,
                'page' => $data['page'] ?? null,
                'delivered_page' => $data['delivered_page'] ?? null,
                'per_page' => $data['per_page'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('card_saved', "\u{53D1}\u{5361}\u{987A}\u{5E8F}\u{5DF2}\u{66F4}\u{65B0}\u{3002}");
    }

    public function exportCards(Request $request): Response
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['required', 'integer'],
        ]);

        [$category, $product] = $this->resolveCardTargets($data['category'], (int) $data['product_id']);

        $selectedIds = collect($data['ids'] ?? [])
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        $cardsQuery = ProductCard::query()
            ->where('product_id', $product->id)
            ->where('status', self::CARD_UNUSED)
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($selectedIds !== []) {
            $cardsQuery->whereIn('id', $selectedIds);
        }

        $cardValues = $cardsQuery->pluck('card_value')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values();

        if ($cardValues->isEmpty()) {
            return redirect()
                ->route('admin.cards', [
                    'category' => $category->slug,
                    'product' => $product->id,
                ])
                ->withErrors([
                    'card_export' => "\u{5F53}\u{524D}\u{6CA1}\u{6709}\u{53EF}\u{5BFC}\u{51FA}\u{7684}\u{53EF}\u{552E}\u{5361}\u{5BC6}\u{3002}",
                ]);
        }

        $filename = $this->makeCardExportFilename($product);

        return response()->streamDownload(function () use ($cardValues): void {
            echo $cardValues->implode(PHP_EOL);
            echo PHP_EOL;
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'card_values' => ['required', 'string', 'max:65535'],
            'note' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'delivered_page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);

        [$category, $product] = $this->resolveCardTargets($data['category'], (int) $data['product_id']);
        $lines = $this->normalizeCardLines($data['card_values']);

        if ($lines === []) {
            return back()->withErrors([
                'card_values' => "\u{8BF7}\u{81F3}\u{5C11}\u{8F93}\u{5165}\u{4E00}\u{6761}\u{5361}\u{5BC6}\u{3002}",
            ])->withInput();
        }

        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        foreach ($lines as $line) {
            ProductCard::query()->create([
                'product_id' => $product->id,
                'card_value' => $line,
                'note' => $note !== '' ? $note : null,
                'status' => "\u{672A}\u{4F7F}\u{7528}",
            ]);
        }

        return redirect()
            ->route('admin.cards', array_filter([
                'category' => $category->slug,
                'product' => $product->id,
                'page' => $data['page'] ?? null,
                'delivered_page' => $data['delivered_page'] ?? null,
                'per_page' => $data['per_page'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('card_saved', "\u{5361}\u{5BC6}\u{5DF2}\u{5BFC}\u{5165}\u{3002}");
    }

    public function destroyCards(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'delivered_page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);

        [$category, $product] = $this->resolveCardTargets($data['category'], (int) $data['product_id']);

        ProductCard::query()
            ->where('product_id', $product->id)
            ->whereIn('id', $data['ids'])
            ->delete();

        return redirect()
            ->route('admin.cards', array_filter([
                'category' => $category->slug,
                'product' => $product->id,
                'page' => $data['page'] ?? null,
                'delivered_page' => $data['delivered_page'] ?? null,
                'per_page' => $data['per_page'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('card_saved', "\u{9009}\u{4E2D}\u{5361}\u{5BC6}\u{5DF2}\u{5220}\u{9664}\u{3002}");
    }

    public function updateCard(Request $request, ProductCard $card): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::exists('product_categories', 'slug')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'card_values' => ['required', 'string', 'max:65535'],
            'note' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'delivered_page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ]);

        [$category, $product] = $this->resolveCardTargets($data['category'], (int) $data['product_id']);
        $lines = $this->normalizeCardLines($data['card_values']);

        if ($lines === []) {
            return back()->withErrors([
                'card_values' => "\u{8BF7}\u{81F3}\u{5C11}\u{8F93}\u{5165}\u{4E00}\u{6761}\u{5361}\u{5BC6}\u{3002}",
            ])->withInput();
        }

        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        DB::transaction(function () use ($card, $product, $lines, $note): void {
            /** @var ProductCard $lockedCard */
            $lockedCard = ProductCard::query()
                ->whereKey($card->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCard->update([
                'product_id' => $product->id,
                'card_value' => $lines[0],
                'note' => $note !== '' ? $note : null,
            ]);

            $this->syncDeliveredOrderCards($lockedCard);
        });

        return redirect()
            ->route('admin.cards', array_filter([
                'category' => $category->slug,
                'product' => $product->id,
                'page' => $data['page'] ?? null,
                'delivered_page' => $data['delivered_page'] ?? null,
                'per_page' => $data['per_page'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('card_saved', "\u{5361}\u{5BC6}\u{5DF2}\u{66F4}\u{65B0}\u{3002}");
    }

    public function destroyCard(Request $request, ProductCard $card): RedirectResponse
    {
        $categorySlug = $request->string('category')->toString();
        $productId = $request->integer('product');

        if ($categorySlug === '') {
            $card->loadMissing('product.category');
            $categorySlug = $card->product?->category?->slug ?? '';
        }

        if (! $productId) {
            $card->loadMissing('product');
            $productId = $card->product_id;
        }

        $card->delete();

        return redirect()
            ->route('admin.cards', ['category' => $categorySlug ?: null, 'product' => $productId ?: null])
            ->with('card_saved', "\u{5361}\u{5BC6}\u{5DF2}\u{5220}\u{9664}\u{3002}");
    }

    public function orders(Request $request): View
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->with(['products' => function ($query): void {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedCategory = $this->resolveSelectedCategory($categories, $request->query('category'));
        $products = collect();
        $selectedProduct = null;

        if ($selectedCategory) {
            $productIds = $selectedCategory->products->pluck('id');
            $orderCounts = $productIds->isEmpty()
                ? collect()
                : Order::query()
                    ->select('product_id', DB::raw('count(*) as total'))
                    ->whereIn('product_id', $productIds)
                    ->groupBy('product_id')
                    ->pluck('total', 'product_id');

            $products = $selectedCategory->products->map(function (Product $product) use ($orderCounts) {
                $product->orders_count = (int) ($orderCounts[$product->id] ?? 0);

                return $product;
            });

            $requestedProductId = (int) $request->integer('product');
            $selectedProduct = $products->firstWhere('id', $requestedProductId) ?: $products->first();
        }

        $ordersQuery = Order::query()
            ->with('product:id,name,category_id')
            ->orderByDesc('id');

        if ($selectedProduct) {
            $ordersQuery->where('product_id', $selectedProduct->id);
        } else {
            $ordersQuery->whereRaw('1 = 0');
        }

        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $orders = $ordersQuery->paginate($perPage)->withQueryString();
        $this->hydrateOrderFulfillmentState($orders->getCollection());

        return view('admin.orders', [
            'title' => "\u{8ba2}\u{5355}\u{7ba1}\u{7406}",
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'orders' => $orders,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }
    public function showOrder(Request $request, Order $order): View
    {
        $order->loadMissing('product.category');
        $this->hydrateOrderFulfillmentState(collect([$order]));

        return view('admin.order-show', [
            'title' => "\u{8ba2}\u{5355}\u{8be6}\u{60c5}",
            'subtitle' => "\u{67e5}\u{770b}\u{8ba2}\u{5355}\u{57fa}\u{7840}\u{4fe1}\u{606f}\u{548c}\u{53d1}\u{5361}\u{8bb0}\u{5f55}\u{3002}",
            'order' => $order,
            'deliveredCards' => collect($order->delivered_cards ?? []),
            'returnQuery' => array_filter([
                'category' => $request->query('category'),
                'product' => $request->query('product'),
                'page' => $request->query('page'),
                'per_page' => $request->query('per_page'),
            ], static fn ($value) => $value !== null && $value !== ''),
        ]);
    }
    public function fulfillOrder(
        Request $request,
        Order $order,
        OrderPaymentService $payments,
        OrderFulfillmentService $fulfillment,
    ): RedirectResponse
    {
        $redirectQuery = array_filter([
            'category' => $request->input('category', $request->query('category')),
            'product' => $request->input('product', $request->query('product')),
            'page' => $request->input('page', $request->query('page')),
            'per_page' => $request->input('per_page', $request->query('per_page')),
        ], static fn ($value) => $value !== null && $value !== '');

        $order->loadMissing('product');

        if (! $order->product || ! $order->product->is_active) {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "\u{5f53}\u{524d}\u{8ba2}\u{5355}\u{5173}\u{8054}\u{5546}\u{54c1}\u{4e0d}\u{53ef}\u{7528}\u{3002}");
        }

        if (! in_array($order->status, ["\u{5f85}\u{652f}\u{4ed8}", "\u{5df2}\u{652f}\u{4ed8}"], true)) {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "\u{5f53}\u{524d}\u{8ba2}\u{5355}\u{72b6}\u{6001}\u{4e0d}\u{53ef}\u{53d1}\u{5361}\u{3002}");
        }

        $availableCards = ProductCard::query()
            ->where('product_id', $order->product_id)
            ->where('status', "\u{672A}\u{4F7F}\u{7528}")
            ->count();

        if ($availableCards < (int) $order->quantity) {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "卡密库存不足，无法发卡。");
        }

        $paymentResult = $payments->markManualPaid($order, 'admin');

        if ($paymentResult === 'missing') {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "\u{8ba2}\u{5355}\u{4e0d}\u{5b58}\u{5728}\u{6216}\u{5df2}\u{88ab}\u{5220}\u{9664}\u{3002}");
        }

        if ($paymentResult === 'already_delivered') {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "\u{8ba2}\u{5355}\u{5df2}\u{786e}\u{8ba4}\u{5e76}\u{53d1}\u{8d27}\u{3002}");
        }

        $result = $fulfillment->fulfill($order);

        if ($result === 'out_of_stock') {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "卡密库存不足，无法发卡。");
        }

        if (in_array($result, ['product_missing', 'missing'], true)) {
            return redirect()
                ->route('admin.orders', $redirectQuery)
                ->with('order_notice', "\u{5f53}\u{524d}\u{8ba2}\u{5355}\u{5173}\u{8054}\u{5546}\u{54c1}\u{4e0d}\u{53ef}\u{7528}\u{3002}");
        }

        return redirect()
            ->route('admin.orders', $redirectQuery)
            ->with('order_notice', "收款成功，已自动发卡。");
    }

    public function payments(PaymentProviderRegistry $providers): View
    {
        $payments = PaymentChannel::query()
            ->orderByDesc('is_enabled')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.payments', [
            'title' => "\u{652f}\u{4ed8}\u{7ba1}\u{7406}",
            'subtitle' => "列出当前支付通道，可新增、编辑和启停。",
            'payments' => $payments,
            'providerOptions' => $providers->optionMap(),
            'providerMetadata' => $providers->metadata(),
        ]);
    }

    private function hydrateOrderFulfillmentState(Collection $orders): void
    {
        $productIds = $orders
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $availableCardsByProduct = $productIds->isEmpty()
            ? collect()
            : ProductCard::query()
                ->select('product_id', DB::raw('count(*) as total'))
                ->whereIn('product_id', $productIds)
                ->where('status', "\u{672A}\u{4F7F}\u{7528}")
                ->groupBy('product_id')
                ->pluck('total', 'product_id');

        $orders->each(function (Order $order) use ($availableCardsByProduct): void {
            $availableCards = (int) ($availableCardsByProduct[$order->product_id] ?? 0);

            $order->available_cards_count = $availableCards;
            $order->can_fulfill_now = $availableCards >= (int) $order->quantity;
            $order->show_stock_shortage = in_array(
                $order->status,
                [OrderPaymentService::STATUS_PENDING, OrderPaymentService::STATUS_PAID],
                true
            ) && ! $order->can_fulfill_now;
        });
    }

    public function createPayment(PaymentProviderRegistry $providers): View
    {
        return view('admin.payments-form', [
            'title' => "\u{65b0}\u{589e}\u{652f}\u{4ed8}\u{901a}\u{9053}",
            'subtitle' => "\u{586b}\u{5199}\u{652f}\u{4ed8}\u{901a}\u{9053}\u{7684}\u{57fa}\u{7840}\u{914d}\u{7f6e}\u{3002}",
            'mode' => 'create',
            'paymentRecord' => null,
            'providerOptions' => $providers->optionMap(),
            'providerMetadata' => $providers->metadata(),
        ]);
    }

    public function storePayment(Request $request, PaymentProviderRegistry $providers): RedirectResponse
    {
        $data = $this->validatePaymentData($request, $providers);
        $data['sort_order'] = ((int) PaymentChannel::query()->max('sort_order')) + 1;

        PaymentChannel::query()->create($data);

        return redirect()
            ->route('admin.payments')
            ->with('payment_notice', "\u{652f}\u{4ed8}\u{901a}\u{9053}\u{5df2}\u{65b0}\u{589e}\u{3002}");
    }

    public function editPayment(PaymentChannel $payment, PaymentProviderRegistry $providers): View
    {
        return view('admin.payments-form', [
            'title' => "\u{7f16}\u{8f91}\u{652f}\u{4ed8}\u{901a}\u{9053}",
            'subtitle' => "\u{4fee}\u{6539}\u{652f}\u{4ed8}\u{901a}\u{9053}\u{914d}\u{7f6e}\u{5e76}\u{4fdd}\u{5b58}\u{3002}",
            'mode' => 'edit',
            'paymentRecord' => $payment,
            'providerOptions' => $providers->optionMap(),
            'providerMetadata' => $providers->metadata(),
        ]);
    }

    public function updatePayment(Request $request, PaymentChannel $payment, PaymentProviderRegistry $providers): RedirectResponse
    {
        $data = $this->validatePaymentData($request, $providers);

        $payment->update($data);

        return redirect()
            ->route('admin.payments')
            ->with('payment_notice', "\u{652f}\u{4ed8}\u{901a}\u{9053}\u{5df2}\u{66f4}\u{65b0}\u{3002}");
    }

    public function togglePayment(PaymentChannel $payment): RedirectResponse
    {
        $payment->update([
            'is_enabled' => ! $payment->is_enabled,
        ]);

        return redirect()
            ->route('admin.payments')
            ->with('payment_notice', "\u{652f}\u{4ed8}\u{901a}\u{9053}\u{72b6}\u{6001}\u{5df2}\u{66f4}\u{65b0}\u{3002}");

    }

    private function validatePaymentData(Request $request, PaymentProviderRegistry $providers): array
    {
        $providerKeys = array_keys($providers->optionMap());
        $providerMetadata = $providers->metadata();
        $baseMessages = [
            'name.required' => '请输入支付名称。',
            'name.max' => '支付名称不能超过 40 个字符。',
            'provider.required' => '请选择支付提供方。',
            'provider.in' => '支付提供方参数无效。',
            'payment_mark.required' => '请输入支付标识。',
            'payment_mark.max' => '支付标识不能超过 40 个字符。',
            'route_path.required' => '请输入支付处理路由。',
            'route_path.max' => '支付处理路由不能超过 120 个字符。',
        ];
        $baseData = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'provider' => ['required', 'string', Rule::in($providerKeys)],
            'payment_mark' => ['required', 'string', 'max:40'],
            'route_path' => ['required', 'string', 'max:120'],
            'is_enabled' => ['nullable', 'boolean'],
        ], $baseMessages);

        $provider = trim((string) $baseData['provider']);
        $metadata = $providerMetadata[$provider] ?? [];
        $fields = is_array($metadata['fields'] ?? null) ? $metadata['fields'] : [];
        $paymentScenes = array_values(array_filter(
            array_map(static fn ($value): string => trim((string) $value), (array) ($metadata['payment_scenes'] ?? []))
        ));
        $paymentMethods = array_values(array_filter(
            array_map(static fn ($value): string => trim((string) $value), (array) ($metadata['payment_methods'] ?? []))
        ));
        $defaultScene = in_array($metadata['default_payment_scene'] ?? null, $paymentScenes, true)
            ? (string) $metadata['default_payment_scene']
            : ($paymentScenes[0] ?? 'general');
        $defaultMethod = in_array($metadata['default_payment_method'] ?? null, $paymentMethods, true)
            ? (string) $metadata['default_payment_method']
            : ($paymentMethods[0] ?? 'page');

        $rules = [
            'payment_scene' => ['required', Rule::in($paymentScenes !== [] ? $paymentScenes : [$defaultScene])],
            'payment_method' => ['required', Rule::in($paymentMethods !== [] ? $paymentMethods : [$defaultMethod])],
            'provider_config' => ['nullable', 'array'],
        ];
        $messages = [
            'payment_scene.required' => '请选择支付场景。',
            'payment_scene.in' => '支付场景参数无效。',
            'payment_method.required' => '请选择支付方式。',
            'payment_method.in' => '支付方式参数无效。',
            'provider_config.array' => '支付配置格式无效。',
        ];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $fieldRules = [(bool) ($field['required'] ?? true) ? 'required' : 'nullable', 'string'];
            $maxLength = max(0, (int) ($field['maxlength'] ?? 0));

            if ($maxLength > 0) {
                $fieldRules[] = 'max:' . $maxLength;
            }

            $rules['provider_config.' . $fieldKey] = $fieldRules;

            $fieldLabel = trim((string) ($field['label'] ?? $fieldKey));
            $messages['provider_config.' . $fieldKey . '.required'] = "\u{8bf7}\u{8f93}\u{5165}{$fieldLabel}\u{3002}";

            if ($maxLength > 0) {
                $messages['provider_config.' . $fieldKey . '.max'] = "{$fieldLabel}\u{957f}\u{5ea6}\u{4e0d}\u{80fd}\u{8d85}\u{8fc7} {$maxLength} \u{4e2a}\u{5b57}\u{7b26}\u{3002}";
            }
        }

        $data = array_merge($baseData, $request->validate($rules, $messages));
        $normalizedConfig = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $value = trim((string) data_get($data, 'provider_config.' . $fieldKey, ''));
            if ($value !== '') {
                $normalizedConfig[$fieldKey] = $value;
            }
        }

        $data['provider_config'] = $normalizedConfig;
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['provider'] = $provider;
        $data['name'] = trim((string) $data['name']);
        $data['payment_mark'] = trim((string) $data['payment_mark']);
        $data['payment_scene'] = trim((string) ($data['payment_scene'] ?? $defaultScene));
        $data['payment_method'] = trim((string) ($data['payment_method'] ?? $defaultMethod));
        $data['merchant_id'] = null;
        $data['merchant_public_key'] = null;
        $data['merchant_private_key'] = null;

        if ($provider === 'alipay') {
            $data['merchant_id'] = $normalizedConfig['app_id'] ?? null;
            $data['merchant_public_key'] = $normalizedConfig['public_key'] ?? null;
            $data['merchant_private_key'] = $normalizedConfig['private_key'] ?? null;
        }

        $defaultRoutePath = $providerMetadata[$provider]['route_path'] ?? '/payments/' . $provider . '/start';
        $routePath = trim((string) ($data['route_path'] ?? ''));
        $data['route_path'] = '/' . ltrim($routePath !== '' ? $routePath : $defaultRoutePath, '/');

        return $data;
    }

    private function productValidationMessages(): array
    {
        return [
            'category_id.required' => '请选择所属商品分类。',
            'category_id.exists' => '所选商品分类不存在。',
            'name.required' => '请输入商品名称。',
            'name.max' => '商品名称不能超过 80 个字符。',
            'compare_price.regex' => '划线价格式不正确，最多支持 2 位小数。',
            'price.required' => '请输入售价。',
            'price.regex' => '售价格式不正确，最多支持 2 位小数。',
            'description_html.max' => '商品描述内容不能超过 65535 个字符。',
            'detail_tag_style.in' => '标签视觉风格参数无效。',
            'detail_tags.max' => '详情卡标签数据不能超过 10000 个字符。',
            'image.file' => '商品图片上传内容必须是文件。',
            'image.mimes' => '商品图片仅支持 jpg、jpeg、png、webp、gif、avif 格式。',
            'image.max' => '商品图片不能超过 3 MB。',
        ];
    }

    private function resolveSelectedCategory(Collection $categories, mixed $slug): ?ProductCategory
    {
        if (is_string($slug) && $slug !== '') {
            $matched = $categories->firstWhere('slug', $slug);

            if ($matched) {
                return $matched;
            }
        }

        return $categories->first();
    }

    private function resolveEditorCategorySlug(array $categories, mixed $slug): ?string
    {
        $validCategoryIds = array_column($categories, 'id');

        if (is_string($slug) && in_array($slug, $validCategoryIds, true)) {
            return $slug;
        }

        return $categories[0]['id'] ?? null;
    }

    private function resolveCardTargets(string $categorySlug, int $productId): array
    {
        $category = ProductCategory::query()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $product = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($product->category_id === $category->id, 422, "\u{5546}\u{54C1}\u{5206}\u{7C7B}\u{4E0D}\u{5339}\u{914D}\u{3002}");

        return [$category, $product];
    }

    private function makeCardExportFilename(Product $product): string
    {
        $label = trim((string) $product->name);
        $label = preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $label) ?? '';
        $label = trim($label, '-');

        if ($label === '') {
            $label = 'product-' . $product->id;
        }

        return $label . '-cards-' . now()->format('Ymd-His') . '.txt';
    }

    private function normalizeCardLines(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', trim($value)) ?: []
        )));
    }

    private function extractOrderNoFromCardNote(?string $note): ?string
    {
        $value = trim((string) $note);

        if ($value === '') {
            return null;
        }

        if (preg_match('/ORDER:([A-Z0-9]+)/i', $value, $matches) === 1) {
            return strtoupper((string) ($matches[1] ?? ''));
        }

        if (preg_match('/([A-Z]{2,}[0-9]{6,}[A-Z0-9]*)/i', $value, $matches) === 1) {
            return strtoupper((string) ($matches[1] ?? ''));
        }

        return null;
    }

    private function syncDeliveredOrderCards(ProductCard $card): void
    {
        if ((string) $card->status === self::CARD_UNUSED) {
            return;
        }

        $orderNo = $this->extractOrderNoFromCardNote((string) $card->note);

        if (! $orderNo) {
            return;
        }

        $order = Order::query()
            ->where('order_no', $orderNo)
            ->where('product_id', $card->product_id)
            ->lockForUpdate()
            ->first();

        if (! $order) {
            return;
        }

        $currentCards = ProductCard::query()
            ->where('product_id', $card->product_id)
            ->where('status', '!=', self::CARD_UNUSED)
            ->where('note', 'like', '%ORDER:' . $orderNo . '%')
            ->orderBy('id')
            ->pluck('card_value')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        if ($currentCards === []) {
            return;
        }

        $order->update([
            'delivered_cards' => $this->reconcileDeliveredCardSnapshot($order->delivered_cards ?? [], $currentCards),
        ]);
    }

    private function reconcileDeliveredCardSnapshot(array $existingSnapshot, array $currentCards): array
    {
        $remainingCards = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $currentCards
        ), static fn (string $value): bool => $value !== ''));

        if ($remainingCards === []) {
            return [];
        }

        $snapshot = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $existingSnapshot
        ), static fn (string $value): bool => $value !== ''));

        if ($snapshot === []) {
            return $remainingCards;
        }

        $reconciled = [];

        foreach ($snapshot as $value) {
            $matchedIndex = array_search($value, $remainingCards, true);

            if ($matchedIndex !== false) {
                $reconciled[] = $remainingCards[$matchedIndex];
                array_splice($remainingCards, (int) $matchedIndex, 1);
                continue;
            }

            if ($remainingCards !== []) {
                $reconciled[] = array_shift($remainingCards);
            }
        }

        return array_values(array_merge($reconciled, $remainingCards));
    }
}
