<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\RichTextSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CatalogService
{
    public function adminProductIndex(?string $selectedCategorySlug = null): array
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $validSlugs = $categories->pluck('slug')->all();
        $selectedCategorySlug = is_string($selectedCategorySlug) && in_array($selectedCategorySlug, $validSlugs, true)
            ? $selectedCategorySlug
            : 'all';

        $productsQuery = Product::query()
            ->select('products.*')
            ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->where('products.is_active', true)
            ->where('product_categories.is_active', true)
            ->with('category')
            ->withCount(['cards as available_cards_count' => function ($query): void {
                $query->where('status', '未使用');
            }])
            ->orderBy('product_categories.sort_order')
            ->orderBy('products.sort_order')
            ->orderBy('products.id');

        if ($selectedCategorySlug !== 'all') {
            $productsQuery->where('product_categories.slug', $selectedCategorySlug);
        }

        $products = $productsQuery->get();
        $selectedCategory = $categories->firstWhere('slug', $selectedCategorySlug);

        return [
            'selected_category_slug' => $selectedCategorySlug,
            'selected_category_name' => $selectedCategory?->name ?? '全部分类',
            'total_products' => (int) $categories->sum('products_count'),
            'categories' => $categories->map(fn (ProductCategory $category): array => [
                'id' => $category->slug,
                'name' => $category->name,
                'product_count' => (int) $category->products_count,
            ])->all(),
            'products' => $products->map(function (Product $product): array {
                $availableCards = (int) ($product->available_cards_count ?? 0);

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category_slug' => $product->category?->slug,
                    'category_name' => $product->category?->name,
                    'price' => number_format((float) $product->price, 2, '.', ''),
                    'compare_price' => $product->compare_price !== null
                        ? number_format((float) $product->compare_price, 2, '.', '')
                        : null,
                    'stock' => $availableCards,
                    'sold_count' => (int) $product->sold_count,
                    'status' => $product->status,
                ];
            })->all(),
        ];
    }

    public function categoryOptions(): array
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'name'])
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->slug,
                'name' => $category->name,
                'product_count' => (int) $category->products_count,
            ])
            ->all();
    }

    public function createCategory(string $name): ProductCategory
    {
        $name = trim($name);
        $nextSortOrder = (int) (ProductCategory::query()->max('sort_order') ?? -1) + 1;

        return ProductCategory::query()->create([
            'slug' => $this->makeCategorySlug($name),
            'name' => $name,
            'sort_order' => $nextSortOrder,
            'is_active' => true,
        ]);
    }

    public function renameCategory(ProductCategory $category, string $name): ProductCategory
    {
        $name = trim($name);

        $attributes = [
            'name' => $name,
        ];

        if ($category->name !== $name) {
            $attributes['slug'] = $this->makeCategorySlug($name, $category->id);
        }

        $category->update($attributes);

        return $category->refresh();
    }

    public function deleteCategory(ProductCategory $category): bool
    {
        if ($category->products()->where('is_active', true)->exists()) {
            return false;
        }

        $category->update([
            'is_active' => false,
        ]);

        return true;
    }

    public function createProduct(array $data): Product
    {
        $category = ProductCategory::query()
            ->where('slug', $data['category_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $nextSortOrder = (int) ($category->products()->where('is_active', true)->max('sort_order') ?? -1) + 1;

        return Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'product-' . Str::lower((string) Str::ulid()),
            'name' => trim((string) $data['name']),
            'price' => number_format((float) $data['price'], 2, '.', ''),
            'compare_price' => $this->normalizeComparePrice($data['compare_price'] ?? null),
            'stock' => 0,
            'sold_count' => 0,
            'status' => '上架中',
            'sort_order' => $nextSortOrder,
            'image_path' => $this->storeImageUpload($data['image'] ?? null),
            'description_html' => $this->normalizeDescription((string) ($data['description_html'] ?? '')),
            'detail_tag_style' => $this->normalizeDetailTagStyle($data['detail_tag_style'] ?? null),
            'detail_tags' => $this->normalizeDetailTags($data['detail_tags'] ?? null),
            'is_active' => true,
        ]);
    }

    public function updateProduct(Product $product, array $data): void
    {
        $category = ProductCategory::query()
            ->where('slug', $data['category_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $attributes = [
            'category_id' => $category->id,
            'name' => trim((string) $data['name']),
            'price' => number_format((float) $data['price'], 2, '.', ''),
            'compare_price' => $this->normalizeComparePrice($data['compare_price'] ?? null),
            'description_html' => $this->normalizeDescription((string) ($data['description_html'] ?? '')),
            'detail_tag_style' => $this->normalizeDetailTagStyle($data['detail_tag_style'] ?? null),
            'detail_tags' => $this->normalizeDetailTags($data['detail_tags'] ?? null),
        ];

        if ($product->category_id !== $category->id) {
            $attributes['sort_order'] = (int) ($category->products()->where('is_active', true)->max('sort_order') ?? -1) + 1;
        }

        if (($data['remove_image'] ?? false) && $product->image_path) {
            $this->deleteManagedImage($product->image_path);
            $attributes['image_path'] = null;
        }

        if (($data['image'] ?? null) instanceof UploadedFile) {
            $this->deleteManagedImage($product->image_path);
            $attributes['image_path'] = $this->storeImageUpload($data['image']);
        }

        $product->update($attributes);
    }

    public function toggleProductStatus(Product $product): Product
    {
        $product->update([
            'status' => $product->status === '已下架' ? '上架中' : '已下架',
        ]);

        return $product->refresh();
    }

    public function deactivateProduct(Product $product): void
    {
        $product->update([
            'is_active' => false,
            'status' => '已删除',
        ]);
    }

    public function reorderCategories(array $slugs): bool
    {
        $validSlugs = ProductCategory::query()
            ->where('is_active', true)
            ->pluck('slug')
            ->all();

        if (! $this->hasSameIds($slugs, $validSlugs)) {
            return false;
        }

        DB::transaction(function () use ($slugs): void {
            foreach (array_values($slugs) as $index => $slug) {
                ProductCategory::query()
                    ->where('slug', $slug)
                    ->update(['sort_order' => $index]);
            }
        });

        return true;
    }

    public function reorderProducts(string $categorySlug, array $skus): bool
    {
        $category = ProductCategory::query()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            return false;
        }

        $validSkus = Product::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->pluck('sku')
            ->all();

        if (! $this->hasSameIds($skus, $validSkus)) {
            return false;
        }

        DB::transaction(function () use ($category, $skus): void {
            foreach (array_values($skus) as $index => $sku) {
                Product::query()
                    ->where('category_id', $category->id)
                    ->where('sku', $sku)
                    ->update(['sort_order' => $index]);
            }
        });

        return true;
    }

    private function makeCategorySlug(string $name, ?int $ignoreCategoryId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'category';
        $slug = $base;
        $suffix = 1;

        $exists = function (string $value) use ($ignoreCategoryId): bool {
            return ProductCategory::query()
                ->when($ignoreCategoryId, fn ($query) => $query->whereKeyNot($ignoreCategoryId))
                ->where('slug', $value)
                ->exists();
        };

        while ($exists($slug)) {
            $suffix++;
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }

    private function normalizeDescription(string $html): ?string
    {
        $html = RichTextSanitizer::sanitize($html);
        $plain = trim(strip_tags(preg_replace('/<img\b[^>]*>/i', ' [image] ', str_replace(['&nbsp;', "\xc2\xa0"], ' ', $html))));

        if ($plain === '') {
            return null;
        }

        return $html;
    }

    private function normalizeDetailTagStyle(mixed $value): ?string
    {
        $style = trim((string) $value);

        return in_array($style, ['glass', 'minimal', 'gradient'], true) ? $style : null;
    }

    private function normalizeDetailTags(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (! is_array($decoded)) {
                return null;
            }

            $value = $decoded;
        }

        if (! is_array($value)) {
            return null;
        }

        $allowedTones = ['blue', 'violet', 'sky', 'slate', 'green', 'mint', 'emerald', 'lime'];
        $normalized = [];

        foreach (array_values($value) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($item['text'] ?? ''))) ?? '');
            $icon = trim((string) ($item['icon'] ?? ''));
            $tone = trim((string) ($item['tone'] ?? 'blue'));

            if ($text === '' || $icon === '') {
                continue;
            }

            $normalized[] = [
                'text' => function_exists('mb_substr') ? mb_substr($text, 0, 18) : Str::limit($text, 18, ''),
                'icon' => function_exists('mb_substr') ? mb_substr($icon, 0, 4) : substr($icon, 0, 4),
                'tone' => in_array($tone, $allowedTones, true) ? $tone : 'blue',
            ];

            if (count($normalized) >= 8) {
                break;
            }
        }

        return $normalized !== [] ? $normalized : null;
    }

    private function normalizeComparePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function storeImageUpload(?UploadedFile $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        $relativeDirectory = 'uploads/products/' . now()->format('Y/m');
        $absoluteDirectory = public_path($relativeDirectory);
        File::ensureDirectoryExists($absoluteDirectory);

        $extension = $file->extension() ?: ($file->getClientOriginalExtension() ?: 'png');
        $fileName = Str::lower((string) Str::ulid()) . '.' . strtolower($extension);
        $file->move($absoluteDirectory, $fileName);

        return $relativeDirectory . '/' . $fileName;
    }

    private function deleteManagedImage(?string $path): void
    {
        $normalizedPath = ltrim((string) $path, '/');

        if ($normalizedPath === '' || ! Str::startsWith($normalizedPath, 'uploads/products/')) {
            return;
        }

        $absolutePath = public_path($normalizedPath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function hasSameIds(array $incoming, array $valid): bool
    {
        sort($incoming);
        sort($valid);

        return $incoming === $valid;
    }
}
