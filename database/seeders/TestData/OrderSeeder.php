<?php

namespace Database\Seeders\TestData;

use App\Models\Address;
use App\Models\Order\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'buyer_email' => 'buyer1@gmail.com',
                'products' => ['تيشيرت أسود كلاسيك', 'جاكيت هودي حضري'],
                'status' => 'processing',
                'payment_status' => 'paid',
                'payment_method' => 'card',
                'phone' => '01012345678',
            ],
            [
                'buyer_email' => 'buyer2@gmail.com',
                'products' => ['فستان كتان موف', 'شورت أطفال سماوي'],
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'phone' => '01198765432',
            ],
        ];

        foreach ($orders as $data) {
            $buyer = User::query()->where('email', $data['buyer_email'])->firstOrFail();
            $address = Address::query()
                ->where('user_id', $buyer->id)
                ->where('is_default', true)
                ->firstOrFail();
            $subtotal = Product::query()->whereIn('title', $data['products'])->sum('price');

            Order::create([
                'user_id' => $buyer->id,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total_price' => $subtotal,
                'phone' => $data['phone'],
                'address_id' => $address->id,
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_method'],
            ]);
        }
    }
}
