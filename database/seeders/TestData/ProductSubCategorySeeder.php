<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Models\ProductSubCategory;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class ProductSubCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::products() as $definition) {
            ProductSubCategory::create([
                'product_id' => Product::query()->where('title', $definition['title'])->firstOrFail()->id,
                'sub_category_id' => SubCategory::query()->where('title', $definition['sub_category'])->firstOrFail()->id,
            ]);
        }
    }
}
