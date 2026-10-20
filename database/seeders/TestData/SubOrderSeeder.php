<?php

namespace Database\Seeders\TestData;

use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubOrderSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->where('email', 'printore@gmail.com')->firstOrFail();
        $subOrders = [
            'buyer1@gmail.com' => [
                'products' => ['تيشيرت أسود كلاسيك', 'جاكيت هودي حضري'],
                'status' => 'processing',
            ],
            'buyer2@gmail.com' => [
                'products' => ['فستان كتان موف', 'شورت أطفال سماوي'],
                'status' => 'completed',
            ],
        ];

        foreach ($subOrders as $buyerEmail => $data) {
            $buyer = User::query()->where('email', $buyerEmail)->firstOrFail();
            $order = Order::query()->where('user_id', $buyer->id)->firstOrFail();
            $subtotal = Product::query()->whereIn('title', $data['products'])->sum('price');

            SubOrder::create([
                'order_id' => $order->id,
                'seller_id' => $seller->id,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total_price' => $subtotal,
                'status' => $data['status'],
            ]);
        }
    }
}
