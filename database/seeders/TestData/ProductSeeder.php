<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::products() as $definition) {
            $product = collect($definition)->only([
                'title', 'description', 'cover_image', 'price', 'discount_amount',
            ])->all();
            $seller = User::query()->where('email', $definition['seller_email'])->firstOrFail();

            Product::create([
                ...$product,
                'seller_id' => $seller->id,
                'is_active' => true,
            ]);
        }
    }
}
