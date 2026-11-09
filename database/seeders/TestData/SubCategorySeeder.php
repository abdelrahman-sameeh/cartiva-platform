<?php

namespace Database\Seeders\TestData;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $subCategories = collect(ProductCatalog::products())
            ->groupBy('category')
            ->map(fn ($products) => $products->pluck('sub_category')->unique());

        foreach ($subCategories as $categoryTitle => $titles) {
            $category = Category::query()->where('title', $categoryTitle)->firstOrFail();

            foreach ($titles as $title) {
                SubCategory::create([
                    'title' => $title,
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
