<?php

namespace Database\Seeders\TestData;

use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $cartItems = [
            'buyer1@gmail.com' => [
                'تيشيرت أسود كلاسيك' => 1,
                'جاكيت هودي حضري' => 2,
            ],
            'buyer2@gmail.com' => [
                'فستان كتان موف' => 1,
                'شورت أطفال سماوي' => 1,
            ],
        ];

        foreach ($cartItems as $buyerEmail => $products) {
            $buyer = User::query()->where('email', $buyerEmail)->firstOrFail();
            $cart = Cart::query()->where('user_id', $buyer->id)->firstOrFail();

            foreach ($products as $productTitle => $quantity) {
                $product = Product::query()->where('title', $productTitle)->firstOrFail();
                $variant = $product->variants()->where('stock', '>=', $quantity)->firstOrFail();

                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }
}
