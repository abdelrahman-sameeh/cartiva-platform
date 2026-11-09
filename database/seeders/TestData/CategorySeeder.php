<?php

namespace Database\Seeders\TestData;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(ProductCatalog::products())->pluck('category')->unique();

        foreach ($categories as $title) {
            Category::create(['title' => $title]);
        }
    }
}
