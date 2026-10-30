<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Payments\SellerProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerFeeCalculatorController extends Controller
{
    public function __invoke(Request $request, SellerProfitCalculator $calculator): View
    {
        $result = null;

        if ($request->filled('price')) {
            $validated = $request->validate([
                'price' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
                'cost' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999.99'],
                'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
                'usd_egp_rate' => ['required', 'decimal:0,4', 'min:1', 'max:1000'],
                'international_card' => ['nullable', 'boolean'],
            ]);

            $result = $calculator->calculate(
                $validated['price'],
                isset($validated['cost']) && $validated['cost'] !== '' ? $validated['cost'] : null,
                (int) $validated['quantity'],
                $validated['usd_egp_rate'],
                (bool) ($validated['international_card'] ?? false),
            );
        }

        return view('seller.fee-calculator', [
            'result' => $result,
            'defaultUsdEgpRate' => config('services.stripe.fees.estimated_usd_egp_rate', 56),
            'platformFeePercent' => config('services.stripe.fees.platform_commission_bps', 500) / 100,
        ]);
    }
}
