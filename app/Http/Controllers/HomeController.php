<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Payments\PaymentProviderRegistry;
use App\Support\RichTextSanitizer;
use App\Support\StorefrontProductResolver;
use App\Support\StorefrontTheme;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->string('fragment')->toString() === 'products') {
            return $this->catalogProducts($request);
        }

        $storefrontTheme = SiteSetting::current()->resolvedFrontendTheme();

        $categories = $this->loadActiveCategories();
        $selectedCategorySlug = $this->resolveSelectedCategorySlug(
            $request->string('category')->toString(),
            $categories->pluck('slug'),
        );

        $categoryItems = array_merge([[
            'slug' => '',
            'name' => "\u{5168}\u{90E8}",
            'product_count' => (int) $categories->sum('products_count'),
            'is_active' => $selectedCategorySlug === '',
        ]], $categories->map(fn (ProductCategory $category): array => [
            'slug' => $category->slug,
            'name' => $category->name,
            'product_count' => (int) $category->products_count,
            'is_active' => $selectedCategorySlug === $category->slug,
        ])->all());

        return view(StorefrontTheme::view('home', $storefrontTheme), [
            'categories' => $categoryItems,
            'selectedCategorySlug' => $selectedCategorySlug,
            'activeProducts' => $this->loadStorefrontProducts($selectedCategorySlug),
        ]);
    }

    public function show(Product $product, PaymentProviderRegistry $providers, StorefrontProductResolver $resolver): View
    {
        $storefrontTheme = SiteSetting::current()->resolvedFrontendTheme();
        $product = $resolver->resolve($product);
        $product->loadMissing('category');
        $product->loadCount(['cards as available_stock' => function ($query): void {
            $query->where('status', '未使用');
        }]);

        $productData = $this->mapProduct($product);

        return view(StorefrontTheme::view('product-show', $storefrontTheme), [
            'productData' => $productData,
            'maxQuantity' => max(1, min(20, (int) $productData['stock'])),
            'paymentChannels' => $this->loadEnabledPaymentChannels($providers),
        ]);
    }

    private function catalogProducts(Request $request): JsonResponse
    {
        $storefrontTheme = SiteSetting::current()->resolvedFrontendTheme();
        $requestedCategorySlug = $request->string('category')->toString();
        $selectedCategorySlug = $this->resolveSelectedCategorySlug(
            $requestedCategorySlug,
            $this->loadActiveCategorySlugs(),
        );

        return response()->json([
            'html' => view(StorefrontTheme::view('partials.storefront-products', $storefrontTheme), [
                'products' => $this->loadStorefrontProducts($selectedCategorySlug),
            ])->render(),
            'selectedCategorySlug' => $selectedCategorySlug,
            'url' => $requestedCategorySlug === 'all'
                ? route('home', ['category' => 'all'])
                : ($requestedCategorySlug !== '' && $selectedCategorySlug !== ''
                ? route('home', ['category' => $selectedCategorySlug])
                : route('home')),
        ]);
    }

    private function loadActiveCategories(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($query): void {
                $query->where('is_active', true)->where('status', "\u{4E0A}\u{67B6}\u{4E2D}");
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function loadActiveCategorySlugs(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('slug');
    }

    private function loadEnabledPaymentChannels(PaymentProviderRegistry $providers): array
    {
        return PaymentChannel::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (PaymentChannel $channel): bool => (bool) ($providers->forChannel($channel)?->isChannelEnabled($channel)))
            ->values()
            ->map(fn (PaymentChannel $channel): array => [
                'id' => $channel->id,
                'provider' => trim((string) $channel->provider),
                'name' => trim((string) $channel->name) !== ''
                    ? trim((string) $channel->name)
                    : ($providers->forChannel($channel)?->label() ?? trim((string) $channel->provider)),
            ])
            ->all();
    }

    private function resolveSelectedCategorySlug(string $requestedSlug, Collection $categorySlugs): string
    {
        if ($requestedSlug === 'all') {
            return '';
        }

        if ($requestedSlug !== '' && $categorySlugs->contains($requestedSlug)) {
            return $requestedSlug;
        }

        return (string) ($categorySlugs->first() ?? '');
    }

    private function loadStorefrontProducts(string $selectedCategorySlug): array
    {
        $productsQuery = Product::query()
            ->select('products.*')
            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->where('products.is_active', true)
            ->where('products.status', '上架中')
            ->where('product_categories.is_active', true)
            ->with('category')
            ->withCount(['cards as available_stock' => function ($query): void {
                $query->where('status', '未使用');
            }])
            ->orderBy('product_categories.sort_order')
            ->orderBy('products.sort_order')
            ->orderBy('products.id');

        if ($selectedCategorySlug !== '') {
            $productsQuery->where('product_categories.slug', $selectedCategorySlug);
        }

        return $productsQuery->get()
            ->map(fn (Product $product): array => $this->mapProduct($product))
            ->all();
    }


    private function mapProduct(Product $product): array
    {
        $stock = (int) ($product->available_stock ?? $product->cards()->where('status', '未使用')->count());

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => number_format((float) $product->price, 2, '.', ''),
            'compare_price' => $product->compare_price !== null
                ? number_format((float) $product->compare_price, 2, '.', '')
                : null,
            'stock' => $stock,
            'stock_tone' => $this->stockTone($stock),
            'stock_label' => $this->stockLabel($stock),
            'image' => $product->image_path ?: 'product-placeholder.svg',
            'description_html' => RichTextSanitizer::sanitize((string) $product->description_html),
            'detail_tag_style' => in_array((string) $product->detail_tag_style, ['glass', 'minimal', 'gradient'], true)
                ? (string) $product->detail_tag_style
                : 'glass',
            'detail_tags' => $this->normalizeDetailTags($product->detail_tags),
            'category_name' => $product->category?->name,
            'category_slug' => $product->category?->slug,
            'status' => $product->status,
        ];
    }

    private function normalizeDetailTags(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $allowedTones = ['blue', 'violet', 'sky', 'slate', 'green', 'mint', 'emerald', 'lime'];

        return collect($value)
            ->filter(fn ($item): bool => is_array($item))
            ->map(function (array $item) use ($allowedTones): ?array {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($item['text'] ?? ''))) ?? '');
                $icon = trim((string) ($item['icon'] ?? ''));
                $tone = trim((string) ($item['tone'] ?? 'blue'));

                if ($text === '' || $icon === '') {
                    return null;
                }

                return [
                    'text' => function_exists('mb_substr') ? mb_substr($text, 0, 18) : $text,
                    'icon' => function_exists('mb_substr') ? mb_substr($icon, 0, 4) : $icon,
                    'tone' => in_array($tone, $allowedTones, true) ? $tone : 'blue',
                ];
            })
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }

    private function stockLabel(int $stock): string
    {
        return match ($this->stockTone($stock)) {
            'plenty' => '库存充足',
            'normal' => '库存一般',
            'tight' => '库存紧张',
            'low' => '即将告罄',
            default => '暂时缺货',
        };
    }

    private function stockTone(int $stock): string
    {
        if ($stock <= 0) {
            return 'out';
        }

        if ($stock < 10) {
            return 'low';
        }

        if ($stock < 30) {
            return 'tight';
        }

        if ($stock <= 50) {
            return 'normal';
        }

        return 'plenty';
    }
}
