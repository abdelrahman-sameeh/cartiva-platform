<?php

namespace Database\Seeders\TestData;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $subCategories = [
            'ملابس رجالي' => ['تيشيرتات رجالي', 'ملابس كاجوال رجالي', 'هوديز رجالي'],
            'ملابس حريمي' => ['تيشيرتات حريمي', 'ملابس كاجوال حريمي', 'جاكيتات حريمي', 'فساتين حريمي'],
            'ملابس أطفال' => ['تيشيرتات أطفال', 'ملابس رياضية أطفال', 'شورتات أطفال'],
        ];

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
