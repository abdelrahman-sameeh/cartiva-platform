<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Services\OrderService;
use App\Services\Payments\OrderCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class OrderCheckoutController extends Controller
{
    public function __construct(
        private readonly OrderCheckoutService $checkoutService,
        private readonly OrderService $orderService
    ) {}

    public function store(Request $request, Order $order): RedirectResponse
    {
        try {
            $url = $this->checkoutService->checkoutUrl(
                $request->user(),
                $order,
                route('orders.payment.return', $order).'?session_id={CHECKOUT_SESSION_ID}',
                route('orders.show', $order).'?payment=cancelled'
            );
        } catch (ApiErrorException $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => 'تعذر بدء الدفع عبر Stripe حاليًا. حاول مرة أخرى.',
            ]);
        }

        return redirect()->away($url);
    }

    public function returned(Request $request, Order $order): RedirectResponse
    {
        $this->orderService->customerOrder($request->user(), $order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'تم الرجوع من Stripe. جاري تأكيد حالة الدفع بشكل آمن.');
    }
}
