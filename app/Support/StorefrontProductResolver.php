<?php

namespace App\Support;

use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontProductResolver
{
    public function resolve(Product $product): Product
    {
        $resolved = Product::query()
            ->with('category')
            ->whereKey($product->getKey())
            ->where('is_active', true)
            ->where('status', "\u{4E0A}\u{67B6}\u{4E2D}")
            ->first();

        if (! $resolved || ! $resolved->category || ! $resolved->category->is_active) {
            throw new NotFoundHttpException();
        }

        return $resolved;
    }
}
