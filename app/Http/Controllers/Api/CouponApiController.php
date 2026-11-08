<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function index(Request $request): JsonResponse
    {
        $coupons = $this->couponService
            ->forSeller($request->user())
            ->latest()
            ->get();

        return response()->json($coupons);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponService->create($request->user(), $request->validated());

        return response()->json($coupon, 201);
    }

    public function show(Request $request, Coupon $coupon): JsonResponse
    {
        return response()->json($this->couponService->ownedBy($request->user(), $coupon));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon = $this->couponService->update($request->user(), $coupon, $request->validated());

        return response()->json($coupon);
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        $this->couponService->delete($request->user(), $coupon);

        return response()->json(['message' => 'تم حذف الكوبون بنجاح.']);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $deletedCount = $this->couponService->deleteAll($request->user());

        return response()->json([
            'message' => 'تم حذف جميع الكوبونات بنجاح.',
            'deleted_count' => $deletedCount,
        ]);
    }
}
