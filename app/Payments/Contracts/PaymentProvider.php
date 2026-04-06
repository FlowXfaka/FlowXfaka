<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Models\PaymentChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface PaymentProvider
{
    public function key(): string;

    public function label(): string;

    public function supportsChannel(PaymentChannel $channel): bool;

    public function isChannelEnabled(PaymentChannel $channel): bool;

    public function canStart(Order $order): bool;

    public function start(Order $order, Request $request): Response|RedirectResponse;

    public function handleNotify(Request $request): Response;

    public function handleReturn(Request $request): RedirectResponse;

    public function supportsStatusSync(): bool;

    public function dispatchStatusSync(Order $order): void;

    public function syncPendingOrder(Order $order): void;

    /**
     * @return array{notify:string,return:string}
     */
    public function callbackUrls(): array;

    public function defaultRoutePath(): string;
}
