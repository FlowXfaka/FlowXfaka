<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('storefront-orders', function (Request $request): array {
            $productKey = (string) optional($request->route('product'))->getRouteKey();
            $contact = trim((string) $request->input('contact'));
            $limits = [
                Limit::perMinute(20)->by('storefront-orders:ip:' . $request->ip()),
                Limit::perMinute(8)->by('storefront-orders:product:' . $productKey . '|' . $request->ip()),
            ];

            if ($contact !== '') {
                $limits[] = Limit::perMinute(4)->by('storefront-orders:contact:' . sha1($contact . '|' . $request->ip()));
            }

            return $limits;
        });

        RateLimiter::for('payment-start', function (Request $request): Limit {
            $orderKey = (string) optional($request->route('order'))->getRouteKey();

            return Limit::perMinute(24)->by('payment-start:' . $request->ip() . '|' . $orderKey);
        });

        RateLimiter::for('order-status', function (Request $request): Limit {
            $orderKey = (string) optional($request->route('order'))->getRouteKey();

            return Limit::perMinute(40)->by('order-status:' . $request->ip() . '|' . $orderKey);
        });

        RateLimiter::for('order-query', function (Request $request): Limit {
            return Limit::perMinute(30)->by('order-query:' . $request->ip());
        });

        RateLimiter::for('order-unlock', function (Request $request): array {
            $contact = trim((string) $request->input('contact'));
            $orderId = trim((string) $request->input('order_id'));
            $limits = [
                Limit::perMinute(20)->by('order-unlock:ip:' . $request->ip()),
            ];

            if ($orderId !== '') {
                $limits[] = Limit::perMinute(6)->by('order-unlock:order:' . $orderId . '|' . $request->ip());
            }

            if ($contact !== '') {
                $limits[] = Limit::perMinute(8)->by('order-unlock:contact:' . sha1($contact . '|' . $request->ip()));
            }

            return $limits;
        });
    }
}
