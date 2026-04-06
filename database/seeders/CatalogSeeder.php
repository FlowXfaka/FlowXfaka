<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sortState = $this->readJson(storage_path('app/admin-product-sorting.json'));
        $customProducts = $this->readCustomProducts(storage_path('app/admin-products.json'));

        $categories = [
            ['slug' => 'game-cards', 'name' => '游戏点卡'],
            ['slug' => 'memberships', 'name' => '会员服务'],
            ['slug' => 'software', 'name' => '软件授权'],
            ['slug' => 'accounts', 'name' => '账号凭证'],
            ['slug' => 'custom', 'name' => '自定义分类'],
        ];

        $products = [
            ['sku' => 'game-card-001', 'category_slug' => 'game-cards', 'name' => '平台兑换卡', 'price' => '99.00', 'stock' => 24, 'status' => '上架中'],
            ['sku' => 'game-card-002', 'category_slug' => 'game-cards', 'name' => '经典点卡包', 'price' => '39.90', 'stock' => 12, 'status' => '上架中'],
            ['sku' => 'vip-001', 'category_slug' => 'memberships', 'name' => '高阶会员月卡', 'price' => '29.90', 'stock' => 28, 'status' => '上架中'],
            ['sku' => 'vip-002', 'category_slug' => 'memberships', 'name' => '季度高级会员', 'price' => '79.00', 'stock' => 9, 'status' => '库存预警'],
            ['sku' => 'soft-001', 'category_slug' => 'software', 'name' => '软件激活码', 'price' => '49.00', 'stock' => 18, 'status' => '上架中'],
            ['sku' => 'soft-002', 'category_slug' => 'software', 'name' => '专业版授权', 'price' => '129.00', 'stock' => 23, 'status' => '上架中'],
            ['sku' => 'acc-001', 'category_slug' => 'accounts', 'name' => '高级权限密钥', 'price' => '69.00', 'stock' => 8, 'status' => '库存预警'],
            ['sku' => 'acc-002', 'category_slug' => 'accounts', 'name' => '平台账号凭证', 'price' => '45.00', 'stock' => 16, 'status' => '上架中'],
            ['sku' => 'custom-001', 'category_slug' => 'custom', 'name' => '自定义测试商品', 'price' => '9.90', 'stock' => 13, 'status' => '上架中'],
            ['sku' => 'custom-002', 'category_slug' => 'custom', 'name' => '云服务时长包', 'price' => '15.00', 'stock' => 10, 'status' => '上架中'],
        ];

        $products = array_merge($products, $customProducts);
        $categoryOrder = array_values(array_filter($sortState['category_order'] ?? [], 'is_string'));
        $productOrders = is_array($sortState['product_orders'] ?? null) ? $sortState['product_orders'] : [];

        foreach ($categories as $index => &$category) {
            $category['sort_order'] = $this->resolveSortOrder($category['slug'], $categoryOrder, $index);
        }
        unset($category);

        DB::transaction(function () use ($categories, $products, $productOrders): void {
            $categoryMap = [];

            foreach ($categories as $category) {
                $model = ProductCategory::query()->updateOrCreate(
                    ['slug' => $category['slug']],
                    [
                        'name' => $category['name'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                    ]
                );

                $categoryMap[$category['slug']] = $model;
            }

            $defaultPositions = [];

            foreach ($products as $product) {
                $categorySlug = $product['category_slug'];

                if (! isset($categoryMap[$categorySlug])) {
                    continue;
                }

                $defaultIndex = $defaultPositions[$categorySlug] ?? 0;
                $defaultPositions[$categorySlug] = $defaultIndex + 1;

                Product::query()->updateOrCreate(
                    ['sku' => $product['sku']],
                    [
                        'category_id' => $categoryMap[$categorySlug]->id,
                        'name' => $product['name'],
                        'price' => number_format((float) $product['price'], 2, '.', ''),
                        'stock' => (int) $product['stock'],
                        'status' => $product['status'] ?: ((int) $product['stock'] < 10 ? '库存预警' : '上架中'),
                        'sort_order' => $this->resolveSortOrder($product['sku'], $productOrders[$categorySlug] ?? [], $defaultIndex),
                        'image_path' => $product['image_path'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }, 5);
    }

    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function readCustomProducts(string $path): array
    {
        $decoded = $this->readJson($path);
        $products = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $sku = trim((string) ($item['id'] ?? ''));
            $categorySlug = trim((string) ($item['category_id'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $price = $item['price'] ?? null;
            $stock = $item['stock'] ?? null;

            if ($sku === '' || $categorySlug === '' || $name === '' || ! is_numeric($price) || ! is_numeric($stock)) {
                continue;
            }

            $products[] = [
                'sku' => $sku,
                'category_slug' => $categorySlug,
                'name' => $name,
                'price' => number_format((float) $price, 2, '.', ''),
                'stock' => max(0, (int) $stock),
                'status' => trim((string) ($item['status'] ?? '上架中')) ?: '上架中',
                'image_path' => $item['image_path'] ?? null,
            ];
        }

        return $products;
    }

    private function resolveSortOrder(string $id, array $orderedIds, int $defaultIndex): int
    {
        $position = array_search($id, $orderedIds, true);

        return $position === false ? 100 + $defaultIndex : $position;
    }
}