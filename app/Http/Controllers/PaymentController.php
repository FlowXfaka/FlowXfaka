<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaymentProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function start(Request $request, Order $order, PaymentProviderRegistry $providers): Response|RedirectResponse
    {
        $provider = $providers->forOrder($order);

        if (! $provider) {
            return redirect()
                ->route('orders.show', ['order' => $order->order_no])
                ->with('order_notice', '当前订单未匹配到可用的支付方式。');
        }

        return $provider->start($order, $request);
    }

    public function notify(Request $request, string $provider, PaymentProviderRegistry $providers): Response
    {
        $paymentProvider = $providers->find($provider);

        if (! $paymentProvider) {
            return response('failure', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return $paymentProvider->handleNotify($request);
    }

    public function handleReturn(Request $request, string $provider, PaymentProviderRegistry $providers): RedirectResponse
    {
        $paymentProvider = $providers->find($provider);

        if (! $paymentProvider) {
            return redirect()->route('home');
        }

        return $paymentProvider->handleReturn($request);
    }
}
