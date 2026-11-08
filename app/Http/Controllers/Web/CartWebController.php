<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartWebController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): View
    {
        return view('cart.index', [
            'cart' => $this->cartService->data($this->cartService->get($request->user())),
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->addItems($request->user(), [[
            'variant_id' => $validated['product_variant_id'],
            'quantity' => $validated['quantity'],
        ]]);

        return redirect()->route('cart.index')->with('success', 'تمت إضافة المنتج إلى السلة.');
    }

    public function updateItem(Request $request, int $item): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['sometimes', 'required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $this->cartService->updateItem(
            $request->user(),
            $item,
            $validated['quantity'],
            $validated['product_variant_id'] ?? null,
        );

        return back()->with('success', 'تم تحديث المنتج في السلة.');
    }

    public function removeItem(Request $request, int $item): RedirectResponse
    {
        $this->cartService->removeItem($request->user(), $item);

        return back()->with('success', 'تم حذف المنتج من السلة.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cartService->clear($request->user());

        return back()->with('success', 'تم إفراغ السلة.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);
        $code = mb_strtoupper(trim($validated['code']));
        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon) {
            return back()->withErrors(['coupon' => 'كود الكوبون غير موجود.'])->withInput();
        }

        $this->cartService->applyCoupon($request->user(), $coupon);

        return back()->with('success', 'تم تطبيق الكوبون بنجاح.');
    }

    public function removeCoupon(Request $request, int $coupon): RedirectResponse
    {
        $this->cartService->removeCoupon($request->user(), $coupon);

        return back()->with('success', 'تمت إزالة الكوبون.');
    }
}
