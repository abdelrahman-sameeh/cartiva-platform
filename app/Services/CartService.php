<?php

namespace App\Services;

use App\Models\Cart\Cart;
use App\Models\Cart\CartCoupon;
use App\Models\Cart\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function get(User $user): ?Cart
    {
        return $user->cart()->first();
    }

    public function addItems(User $user, array $items): Cart
    {
        $cart = $user->cart()->firstOrCreate();

        foreach ($items as $itemData) {
            $variant = ProductVariant::query()->with(['options', 'product'])->findOrFail($itemData['variant_id']);
            $product = $variant->product;
            $item = $cart->items()->where('product_variant_id', $variant->id)->first();
            $quantity = ($item?->quantity ?? 0) + $itemData['quantity'];

            $this->ensureAvailable($variant, $quantity);

            if ($item) {
                $item->update(['quantity' => $quantity]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                ]);
            }
        }

        return $cart;
    }

    public function updateItem(User $user, int $itemId, int $quantity, ?int $variantId = null): CartItem
    {
        $cart = $this->requireCart($user);
        $item = $cart->items()->with('variant.options', 'variant.product')->findOrFail($itemId);
        $variant = $variantId
            ? ProductVariant::query()->with(['options', 'product'])->findOrFail($variantId)
            : $item->variant;

        if ($variant->product_id !== $item->product_id) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'الاختيار المحدد لا يخص هذا المنتج.',
            ]);
        }

        $variantAlreadyExists = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($variantAlreadyExists) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'هذا الاختيار موجود بالفعل في السلة.',
            ]);
        }

        $this->ensureAvailable($variant, $quantity);
        $item->update([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);

        return $item->refresh()->load('variant.options', 'variant.product');
    }

    public function removeItem(User $user, int $itemId): void
    {
        $this->requireCart($user)->items()->findOrFail($itemId)->delete();
    }

    public function clear(User $user): void
    {
        $user->cart()->delete();
    }

    public function applyCoupon(User $user, Coupon $coupon): void
    {
        $cart = $this->requireCart($user);

        if ($coupon->is_invalid()) {
            throw ValidationException::withMessages(['coupon' => 'هذا الكوبون غير صالح للاستخدام.']);
        }

        if ($cart->coupons()->where('coupon_id', $coupon->id)->exists()) {
            throw ValidationException::withMessages(['coupon' => 'تم تطبيق هذا الكوبون بالفعل.']);
        }

        $hasCouponForSeller = $cart->coupons()
            ->whereHas('coupon', fn ($query) => $query->where('seller_id', $coupon->seller_id))
            ->exists();

        if ($hasCouponForSeller) {
            throw ValidationException::withMessages(['coupon' => 'يمكن تطبيق كوبون واحد فقط لكل بائع.']);
        }

        $hasEligibleProduct = $cart->items()
            ->whereHas('product', fn ($query) => $query
                ->where('seller_id', $coupon->seller_id)
                ->where('is_active', true))
            ->exists();

        if (! $hasEligibleProduct) {
            throw ValidationException::withMessages(['coupon' => 'لا تحتوي السلة على منتجات يقبلها هذا الكوبون.']);
        }

        CartCoupon::create([
            'cart_id' => $cart->id,
            'coupon_id' => $coupon->id,
        ]);
    }

    public function removeCoupon(User $user, int $couponId): void
    {
        $this->requireCart($user)
            ->coupons()
            ->where('coupon_id', $couponId)
            ->firstOrFail()
            ->delete();
    }

    public function validate(User $user): array
    {
        $cart = $this->get($user);

        if (! $cart || $cart->items()->doesntExist()) {
            return [
                'valid' => false,
                'errors_count' => 1,
                'errors' => [[
                    'type' => 'empty_cart',
                    'message' => 'السلة فارغة.',
                ]],
            ];
        }

        $cart->load(['items.variant.options', 'items.variant.product', 'coupons.coupon']);
        $errors = [];

        foreach ($cart->items as $item) {
            if (! $item->variant->product->is_active) {
                $errors[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'type' => 'product_inactive',
                    'message' => 'المنتج لم يعد متاحًا.',
                ];
            }

            if ($item->variant->stock < $item->quantity) {
                $errors[] = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'type' => 'insufficient_stock',
                    'message' => "المتاح من اختيار {$item->variant->option_label} هو {$item->variant->stock} فقط.",
                    'requested_quantity' => $item->quantity,
                    'available_quantity' => $item->variant->stock,
                ];
            }
        }

        foreach ($cart->coupons as $cartCoupon) {
            if ($cartCoupon->coupon->is_invalid()) {
                $errors[] = [
                    'coupon_id' => $cartCoupon->coupon_id,
                    'type' => 'invalid_coupon',
                    'message' => 'أحد كوبونات السلة لم يعد صالحًا.',
                ];
            }
        }

        return [
            'valid' => $errors === [],
            'errors_count' => count($errors),
            'errors' => $errors,
            ...($errors === [] ? ['cart' => $this->data($cart)] : []),
        ];
    }

    public function data(?Cart $cart): array
    {
        if (! $cart) {
            return $this->emptyData();
        }

        $cart->load([
            'items.variant.options',
            'items.variant.product.variants.options',
            'coupons.coupon',
        ]);
        $groups = [];

        foreach ($cart->items as $item) {
            $product = $item->variant->product;
            $sellerId = $product->seller_id;
            $groups[$sellerId] ??= [
                'seller_id' => $sellerId,
                'items' => [],
                'coupon' => null,
            ];
            $groups[$sellerId]['items'][] = [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->variant->priceAfterDiscount(),
                'variant' => [
                    'id' => $item->variant->id,
                    'sku' => $item->variant->sku,
                    'options' => $item->variant->option_values,
                    'label' => $item->variant->option_label,
                    'price' => $item->variant->priceAfterDiscount(),
                    'stock' => $item->variant->stock,
                ],
                'product' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'price' => $product->price_after_discount,
                    'cover_image' => $product->cover_image
                        ? $product->cover_image_url
                        : null,
                    'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'options' => $variant->option_values,
                        'label' => $variant->option_label,
                        'price' => $variant->priceAfterDiscount($product),
                        'stock' => $variant->stock,
                    ])->values()->all(),
                ],
            ];
        }

        foreach ($cart->coupons as $cartCoupon) {
            $sellerId = $cartCoupon->coupon->seller_id;

            if (! isset($groups[$sellerId])) {
                continue;
            }

            $groups[$sellerId]['coupon'] = [
                'id' => $cartCoupon->coupon->id,
                'code' => $cartCoupon->coupon->code,
                'percentage' => $cartCoupon->coupon->percentage,
                'expire_date' => $cartCoupon->coupon->expire_date->format('Y-m-d'),
                'is_valid' => ! $cartCoupon->coupon->is_invalid(),
            ];
        }

        $subtotal = 0;
        $discount = 0;

        foreach ($groups as &$group) {
            $groupSubtotal = collect($group['items'])->sum(
                fn (array $item): float => $item['quantity'] * $item['unit_price']
            );
            $groupDiscount = $group['coupon'] && $group['coupon']['is_valid']
                ? $groupSubtotal * ($group['coupon']['percentage'] / 100)
                : 0;

            $group['summary'] = [
                'sub_total' => $groupSubtotal,
                'discount' => $groupDiscount,
                'total' => $groupSubtotal - $groupDiscount,
            ];
            $subtotal += $groupSubtotal;
            $discount += $groupDiscount;
        }
        unset($group);

        return [
            'id' => $cart->id,
            'items_count' => $cart->items->count(),
            'groups' => array_values($groups),
            'summary_cart' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $subtotal - $discount,
            ],
        ];
    }

    private function requireCart(User $user): Cart
    {
        return $user->cart()->firstOrFail();
    }

    private function ensureAvailable(ProductVariant $variant, int $quantity): void
    {
        $product = $variant->product;

        if (! $product->is_active) {
            throw ValidationException::withMessages(['quantity' => 'هذا المنتج غير متاح حاليًا.']);
        }

        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "الكمية المتاحة من {$product->title} ({$variant->option_label}) هي {$variant->stock} فقط.",
            ]);
        }
    }

    private function emptyData(): array
    {
        return [
            'id' => null,
            'items_count' => 0,
            'groups' => [],
            'summary_cart' => [
                'subtotal' => 0,
                'discount' => 0,
                'total' => 0,
            ],
        ];
    }
}
