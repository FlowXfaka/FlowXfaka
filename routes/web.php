<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\StorefrontOrderController;
use App\Http\Middleware\EnsureApplicationInstalled;
use App\Http\Middleware\RedirectIfApplicationInstalled;
use App\Support\InstallLock;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$installRouteBypass = [
    EnsureApplicationInstalled::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
];

Route::get('/install', [InstallController::class, 'show'])
    ->middleware(RedirectIfApplicationInstalled::class)
    ->withoutMiddleware($installRouteBypass)
    ->name('install.show');

Route::post('/install', [InstallController::class, 'store'])
    ->middleware(RedirectIfApplicationInstalled::class)
    ->withoutMiddleware($installRouteBypass)
    ->name('install.store');

Route::get('/', function (Request $request) {
    if (! InstallLock::exists()) {
        return app(InstallController::class)->show();
    }

    return app(HomeController::class)->index($request);
})->name('home');
Route::get('/products/{product:sku}', [HomeController::class, 'show'])->name('products.show');
Route::post('/products/{product:sku}/orders', [StorefrontOrderController::class, 'store'])
    ->middleware('throttle:storefront-orders')
    ->name('orders.store');
Route::get('/orders/{order:order_no}/pay', [PaymentController::class, 'start'])
    ->middleware('throttle:payment-start')
    ->name('payments.start');
Route::post('/payments/{provider}/notify', [PaymentController::class, 'notify'])->name('payments.notify');
Route::get('/payments/{provider}/return', [PaymentController::class, 'handleReturn'])->name('payments.return');
Route::get('/orders/{order:order_no}/status', [StorefrontOrderController::class, 'status'])
    ->middleware('throttle:order-status')
    ->name('orders.status');
Route::post('/orders/unlock', [StorefrontOrderController::class, 'unlock'])
    ->middleware('throttle:order-unlock')
    ->name('orders.unlock');
Route::get('/orders/{order:order_no}', [StorefrontOrderController::class, 'show'])->name('orders.show');
Route::get('/order-query', [StorefrontOrderController::class, 'query'])
    ->middleware('throttle:order-query')
    ->name('order.query');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('admin.guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::post('/account', [AdminAuthController::class, 'updateAccount'])->name('account.update');
        Route::get('/', [AdminController::class, 'overview'])->name('overview');
        Route::post('/overview/low-stock-threshold', [AdminController::class, 'updateLowStockThreshold'])->name('overview.low-stock-threshold');
        Route::get('/products', [AdminController::class, 'products'])->name('products');
        Route::post('/products/categories', [AdminController::class, 'storeCategory'])->name('products.categories.store');
        Route::put('/products/categories/{category:slug}', [AdminController::class, 'updateCategory'])->name('products.categories.update');
        Route::delete('/products/categories/{category:slug}', [AdminController::class, 'destroyCategory'])->name('products.categories.destroy');
        Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
        Route::post('/editor/images', [AdminController::class, 'uploadEditorImage'])->name('editor.images');
        Route::patch('/products/{product}/status', [AdminController::class, 'toggleProductStatus'])->name('products.status');
        Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
        Route::post('/products/categories/order', [AdminController::class, 'reorderCategories'])->name('products.categories.order');
        Route::post('/products/items/order', [AdminController::class, 'reorderProducts'])->name('products.items.order');
        Route::get('/cards', [AdminController::class, 'cards'])->name('cards');
        Route::get('/cards/export', [AdminController::class, 'exportCards'])->name('cards.export');
        Route::post('/cards', [AdminController::class, 'storeCard'])->name('cards.store');
        Route::patch('/cards/dispatch-mode', [AdminController::class, 'updateCardDispatchMode'])->name('cards.dispatch-mode');
        Route::delete('/cards/bulk', [AdminController::class, 'destroyCards'])->name('cards.bulk-destroy');
        Route::put('/cards/{card}', [AdminController::class, 'updateCard'])->name('cards.update');
        Route::delete('/cards/{card}', [AdminController::class, 'destroyCard'])->name('cards.destroy');
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AdminController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{order}/fulfill', [AdminController::class, 'fulfillOrder'])->name('orders.fulfill');
        Route::get('/settings', [SiteSettingController::class, 'show'])->name('settings');
        Route::post('/settings', [SiteSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/editor-images', [SiteSettingController::class, 'uploadEditorImage'])->name('settings.editor-images');
        Route::get('/payments/create', [AdminController::class, 'createPayment'])->name('payments.create');
        Route::post('/payments', [AdminController::class, 'storePayment'])->name('payments.store');
        Route::get('/payments/{payment}/edit', [AdminController::class, 'editPayment'])->name('payments.edit');
        Route::put('/payments/{payment}', [AdminController::class, 'updatePayment'])->name('payments.update');
        Route::patch('/payments/{payment}/toggle', [AdminController::class, 'togglePayment'])->name('payments.toggle');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    });
});
