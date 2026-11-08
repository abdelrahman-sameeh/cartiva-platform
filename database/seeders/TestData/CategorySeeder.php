<?php

namespace Database\Seeders\TestData;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ملابس رجالي', 'ملابس حريمي', 'ملابس أطفال'] as $title) {
            Category::create(['title' => $title]);
        }
    }
}
