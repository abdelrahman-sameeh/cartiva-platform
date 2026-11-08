<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponWebController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function index(Request $request): View
    {
        return view('coupons.index', [
            'coupons' => $this->couponService
                ->forSeller($request->user())
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('coupons.create');
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $coupon = $this->couponService->create($request->user(), $request->validated());

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', "تم إنشاء الكوبون {$coupon->code} بنجاح.");
    }

    public function edit(Request $request, Coupon $coupon): View
    {
        return view('coupons.edit', [
            'coupon' => $this->couponService->ownedBy($request->user(), $coupon),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon = $this->couponService->update($request->user(), $coupon, $request->validated());

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', "تم تعديل الكوبون {$coupon->code} بنجاح.");
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $this->couponService->delete($request->user(), $coupon);

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', "تم حذف الكوبون {$code} بنجاح.");
    }
}
