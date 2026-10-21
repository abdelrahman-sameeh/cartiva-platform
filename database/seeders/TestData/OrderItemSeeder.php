<?php

namespace Database\Seeders\TestData;

use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\SubOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $orderProducts = [
            'buyer1@gmail.com' => ['تيشيرت أسود كلاسيك', 'جاكيت هودي حضري'],
            'buyer2@gmail.com' => ['فستان كتان موف', 'شورت أطفال سماوي'],
        ];

        foreach ($orderProducts as $buyerEmail => $productTitles) {
            $buyer = User::query()->where('email', $buyerEmail)->firstOrFail();
            $order = Order::query()->where('user_id', $buyer->id)->firstOrFail();
            $subOrder = SubOrder::query()->where('order_id', $order->id)->firstOrFail();
            $products = Product::query()->whereIn('title', $productTitles)->get();

            foreach ($products as $product) {
                $variant = $product->variants()->firstOrFail();

                OrderItem::create([
                    'sub_order_id' => $subOrder->id,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'cover_image' => $product->cover_image,
                    'price_at_purchase' => $variant->priceAfterDiscount(),
                    'variant_sku' => $variant->sku,
                    'variant_options' => $variant->option_values,
                    'size' => null,
                    'color' => null,
                    'quantity' => 1,
                    'created_at_snapshot' => $product->created_at,
                ]);
            }
        }
    }
}
