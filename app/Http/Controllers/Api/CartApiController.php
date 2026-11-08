<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartApiController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->get($request->user());

        if (! $cart) {
            return response()->json(['message' => 'Cart does not exist.']);
        }

        return response()->json(['cart' => $this->cartService->data($cart)]);
    }

    public function addItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->cartService->addItems($request->user(), $validated['items']);

        return response()->json([
            'message' => 'Cart updated successfully.',
            'cart' => $this->cartService->data($cart),
        ], 201);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['sometimes', 'required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cartItem = $this->cartService->updateItem(
            $request->user(),
            $item,
            $validated['quantity'],
            $validated['variant_id'] ?? null,
        );

        return response()->json([
            'message' => 'Cart item updated successfully.',
            'data' => $cartItem,
        ]);
    }

    public function removeItem(Request $request, int $item): Response
    {
        $this->cartService->removeItem($request->user(), $item);

        return response()->noContent();
    }

    public function clear(Request $request): Response
    {
        $this->cartService->clear($request->user());

        return response()->noContent();
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate(['coupon_id' => ['required', 'integer', 'exists:coupons,id']]);
        $coupon = Coupon::query()->findOrFail($validated['coupon_id']);
        $this->cartService->applyCoupon($request->user(), $coupon);

        return response()->json(['message' => 'Coupon applied successfully.'], 201);
    }

    public function removeCoupon(Request $request): Response
    {
        $validated = $request->validate(['coupon_id' => ['required', 'integer', 'exists:coupons,id']]);
        $this->cartService->removeCoupon($request->user(), $validated['coupon_id']);

        return response()->noContent();
    }

    public function validateCart(Request $request): JsonResponse
    {
        return response()->json($this->cartService->validate($request->user()));
    }
}
