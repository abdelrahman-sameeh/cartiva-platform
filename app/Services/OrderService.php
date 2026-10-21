<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function forCustomer(User $customer): Builder
    {
        return Order::query()->where('user_id', $customer->id);
    }

    public function customerOrder(User $customer, Order $order): Order
    {
        abort_unless((int) $order->user_id === (int) $customer->id, 403, 'هذا الطلب لا يخص حسابك.');

        return $order->load([
            'address',
            'subOrders.seller:id,first_name,last_name,email',
            'subOrders.items.pictures',
        ]);
    }

    public function create(User $customer, array $data): Order
    {
        if (isset($data['address_id']) && ! $customer->addresses()->whereKey($data['address_id'])->exists()) {
            throw ValidationException::withMessages(['address_id' => 'العنوان المختار لا يخص حسابك.']);
        }

        return DB::transaction(function () use ($customer, $data): Order {
            $cart = $customer->cart()
                ->with(['items.variant.options', 'items.variant.product.pictures', 'coupons.coupon'])
                ->lockForUpdate()
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'السلة فارغة.']);
            }

            foreach ($cart->items as $item) {
                $variant = ProductVariant::query()
                    ->with(['options', 'product.pictures'])
                    ->lockForUpdate()
                    ->find($item->product_variant_id);
                $product = $variant?->product;

                if (! $variant || ! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => "المنتج رقم {$item->product_id} لم يعد متاحًا.",
                    ]);
                }

                if ($variant->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "الكمية المتاحة من {$product->title} ({$variant->option_label}) هي {$variant->stock} فقط.",
                    ]);
                }

                $item->setRelation('variant', $variant);
                $item->setRelation('product', $product);
            }

            $groups = $cart->items->groupBy(fn ($item) => $item->product->seller_id);
            $couponsBySeller = $this->validCouponsBySeller($cart->coupons);
            $subOrdersData = [];
            $usedCoupons = [];
            $orderSubtotal = 0;
            $orderDiscount = 0;

            foreach ($groups as $sellerId => $items) {
                $subtotal = round($items->sum(
                    fn ($item): float => $item->variant->priceAfterDiscount() * $item->quantity
                ), 2);
                $coupon = $couponsBySeller[$sellerId] ?? null;
                $discount = $coupon
                    ? round($subtotal * ((float) $coupon->percentage / 100), 2)
                    : 0;

                if ($coupon) {
                    $usedCoupons[$coupon->id] = $coupon;
                }

                $subOrdersData[$sellerId] = [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $subtotal - $discount,
                ];
                $orderSubtotal += $subtotal;
                $orderDiscount += $discount;
            }

            $order = Order::create([
                'user_id' => $customer->id,
                'subtotal' => $orderSubtotal,
                'discount' => $orderDiscount,
                'total_price' => $orderSubtotal - $orderDiscount,
                'phone' => $data['phone'],
                'address_id' => $data['address_id'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($subOrdersData as $sellerId => $subOrderData) {
                $subOrder = $order->subOrders()->create([
                    'seller_id' => $sellerId,
                    'subtotal' => $subOrderData['subtotal'],
                    'discount' => $subOrderData['discount'],
                    'total_price' => $subOrderData['total'],
                    'status' => 'pending',
                ]);

                foreach ($subOrderData['items'] as $item) {
                    $product = $item->product;
                    $orderItem = $subOrder->items()->create([
                        'product_id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'cover_image' => $product->cover_image,
                        'price_at_purchase' => $item->variant->priceAfterDiscount(),
                        'variant_sku' => $item->variant->sku,
                        'variant_options' => $item->variant->option_values,
                        'size' => null,
                        'color' => null,
                        'quantity' => $item->quantity,
                        'created_at_snapshot' => $product->created_at,
                    ]);

                    $orderItem->pictures()->createMany(
                        $product->pictures->map(fn ($picture): array => [
                            'image_path' => $picture->picture,
                        ])->all()
                    );

                    $item->variant->decrement('stock', $item->quantity);
                    $product->sold_count += $item->quantity;
                    $product->save();
                }
            }

            foreach ($usedCoupons as $coupon) {
                $coupon->increment('used_count');
            }

            $cart->items()->delete();
            $cart->coupons()->delete();

            return $this->customerOrder($customer, $order);
        });
    }

    public function forSeller(User $seller): Builder
    {
        return SubOrder::query()->where('seller_id', $seller->id);
    }

    public function sellerOrder(User $seller, SubOrder $subOrder): SubOrder
    {
        abort_unless((int) $subOrder->seller_id === (int) $seller->id, 403, 'هذا الطلب لا يخص حسابك.');

        return $subOrder->load([
            'order.user:id,first_name,last_name,email',
            'order.address',
            'items.pictures',
        ]);
    }

    public function updateSubOrderStatus(User $seller, SubOrder $subOrder, string $status): SubOrder
    {
        $subOrder = $this->sellerOrder($seller, $subOrder);
        $subOrder->update(['status' => $status]);
        $this->syncOrderStatus($subOrder->order);

        return $subOrder->refresh();
    }

    private function validCouponsBySeller($cartCoupons): array
    {
        $coupons = [];

        foreach ($cartCoupons as $cartCoupon) {
            $coupon = Coupon::query()->lockForUpdate()->find($cartCoupon->coupon_id);

            if ($coupon && ! $coupon->is_invalid()) {
                $coupons[$coupon->seller_id] = $coupon;
            }
        }

        return $coupons;
    }

    private function syncOrderStatus(Order $order): void
    {
        $statuses = $order->subOrders()->pluck('status')->all();
        $uniqueStatuses = array_values(array_unique($statuses));

        if ($uniqueStatuses === ['cancelled']) {
            $order->update(['status' => 'cancelled']);
        } elseif (in_array('pending', $statuses, true) || in_array('processing', $statuses, true)) {
            $order->update(['status' => 'processing']);
        } elseif (in_array('shipped', $statuses, true)) {
            $order->update(['status' => 'shipped']);
        } elseif ($uniqueStatuses === ['completed']) {
            $order->update(['status' => 'completed']);
        }
    }
}
