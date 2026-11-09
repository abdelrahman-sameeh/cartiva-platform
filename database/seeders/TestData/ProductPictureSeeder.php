<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductPictureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::products() as $definition) {
            $product = Product::query()->where('title', $definition['title'])->firstOrFail();
            $product->pictures()->createMany(
                collect($definition['gallery'])
                    ->map(fn (string $picture): array => compact('picture'))
                    ->all()
            );
        }
    }
}
