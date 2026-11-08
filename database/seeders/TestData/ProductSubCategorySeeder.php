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
        $productSubCategories = [
            'تيشيرت أسود كلاسيك' => 'تيشيرتات رجالي',
            'تيشيرت أسود أوفرسايز' => 'تيشيرتات رجالي',
            'جاكيت جينز حريمي' => 'جاكيتات حريمي',
            'فستان كتان موف' => 'فساتين حريمي',
            'جاكيت هودي حضري' => 'هوديز رجالي',
            'شورت أطفال سماوي' => 'شورتات أطفال',
        ];

        foreach ($productSubCategories as $productTitle => $subCategoryTitle) {
            ProductSubCategory::create([
                'product_id' => Product::query()->where('title', $productTitle)->firstOrFail()->id,
                'sub_category_id' => SubCategory::query()->where('title', $subCategoryTitle)->firstOrFail()->id,
            ]);
        }
    }
}
