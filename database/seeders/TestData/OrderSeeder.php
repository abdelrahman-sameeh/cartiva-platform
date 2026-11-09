<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Shipping\DemoShippingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(OrderService $orders, DemoShippingService $shipping): void
    {
        $products = Product::query()->with('variants')->orderBy('id')->get();
        $buyers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::USER->value))
            ->orderBy('id')
            ->skip(18)
            ->take(15)
            ->get();

        foreach ($buyers as $orderIndex => $buyer) {
            for ($itemIndex = 0; $itemIndex < 4; $itemIndex++) {
                $product = $products[(($orderIndex * 11) + ($itemIndex * 3)) % $products->count()];
                $variant = $product->variants[($orderIndex + $itemIndex) % $product->variants->count()];
                $buyer->cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ]);
            }

            $scenario = $orderIndex % 5;
            $paymentMethod = match ($scenario) {
                0, 1 => 'cash',
                4 => 'wallet',
                default => 'card',
            };
            $order = $orders->create($buyer, [
                'phone' => $buyer->phone,
                'address_id' => $buyer->addresses()->where('is_default', true)->value('id'),
                'payment_method' => $paymentMethod,
            ]);

            if (in_array($paymentMethod, ['card', 'wallet'], true)) {
                $paymentStatus = match ($scenario) {
                    2 => Payment::STATUS_PAID,
                    3 => Payment::STATUS_PENDING,
                    default => Payment::STATUS_FAILED,
                };
                $order->payments()->create([
                    'provider' => 'stripe',
                    'provider_payment_id' => $paymentStatus === Payment::STATUS_PAID ? "pi_seed_{$order->id}" : null,
                    'checkout_session_id' => "cs_seed_{$order->id}",
                    'idempotency_key' => (string) Str::uuid(),
                    'amount_minor' => (int) round((float) $order->total_price * 100),
                    'currency' => 'egp',
                    'status' => $paymentStatus,
                    'paid_at' => $paymentStatus === Payment::STATUS_PAID ? now()->subDays($orderIndex + 1) : null,
                ]);
                $order->update(['payment_status' => match ($paymentStatus) {
                    Payment::STATUS_PAID => 'paid',
                    Payment::STATUS_FAILED => 'failed',
                    default => 'pending',
                }]);
            }

            foreach ($order->subOrders()->with('shipment')->get() as $subOrder) {
                $tracking = $subOrder->shipment->tracking_number;

                if ($scenario === 0) {
                    $shipping->handleEvent($tracking, 'in-transit');
                } elseif (in_array($scenario, [1, 2], true)) {
                    $shipping->handleEvent($tracking, 'delivered');

                    if ($scenario === 1) {
                        $shipping->handleEvent($tracking, 'remitted');
                        $shipping->handleEvent($tracking, 'seller-paid');
                    }
                } elseif ($scenario === 3) {
                    $shipping->handleEvent($tracking, 'picked-up');
                } else {
                    $shipping->cancel($subOrder);
                }
            }
        }
    }
}
