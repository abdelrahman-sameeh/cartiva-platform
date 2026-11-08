<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class StripeConnectController extends Controller
{
    public function __construct(private readonly SellerOnboardingService $onboardingService) {}

    public function show(Request $request): View
    {
        return view('seller.stripe-connect.show', [
            'stripeAccount' => $request->user()->stripeAccount()->first(),
        ]);
    }

    public function onboard(Request $request): RedirectResponse
    {
        return $this->redirectToOnboarding($request);
    }

    public function refresh(Request $request): RedirectResponse
    {
        return $this->redirectToOnboarding($request);
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $stripeAccount = $request->user()->stripeAccount()->first();

        if (! $stripeAccount) {
            return redirect()
                ->route('seller.stripe-connect.show')
                ->withErrors(['stripe' => 'لم يتم العثور على حساب Stripe مرتبط بحسابك.']);
        }

        try {
            $this->onboardingService->sync($stripeAccount);
        } catch (ApiErrorException $exception) {
            report($exception);

            return redirect()
                ->route('seller.stripe-connect.show')
                ->withErrors(['stripe' => 'تعذر التحقق من حالة حساب Stripe حاليًا. حاول مرة أخرى.']);
        }

        return redirect()
            ->route('seller.stripe-connect.show')
            ->with('success', 'تم تحديث حالة حساب Stripe.');
    }

    private function redirectToOnboarding(Request $request): RedirectResponse
    {
        try {
            $url = $this->onboardingService->onboardingUrl(
                $request->user(),
                route('seller.stripe-connect.refresh'),
                route('seller.stripe-connect.return')
            );
        } catch (ApiErrorException $exception) {
            report($exception);

            return redirect()
                ->route('seller.stripe-connect.show')
                ->withErrors(['stripe' => 'تعذر بدء إعداد حساب Stripe حاليًا. حاول مرة أخرى.']);
        }

        return redirect()->away($url);
    }
}
