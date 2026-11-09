<?php

namespace Database\Seeders\TestData;

use App\Enums\RoleName;
use App\Models\Cart\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->with('variants')->orderBy('id')->get();
        $buyers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::USER->value))
            ->orderBy('id')
            ->limit(18)
            ->get();

        foreach ($buyers as $buyerIndex => $buyer) {
            $itemCount = 2 + ($buyerIndex % 3);

            for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
                $product = $products[($buyerIndex * 5 + $itemIndex * 7) % $products->count()];
                $variant = $product->variants[($buyerIndex + $itemIndex) % $product->variants->count()];

                CartItem::create([
                    'cart_id' => $buyer->cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1 + (($buyerIndex + $itemIndex) % 2),
                ]);
            }
        }
    }
}
