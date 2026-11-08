<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Str;

class CategoryService
{
  public function createCategory(string $title): Category
  {
    return Category::firstOrCreate(
      ['slug' => Str::slug($title)],
      ['title' => $title],
    );
  }

  public function createSubCategory(string $title, int $categoryId): SubCategory
  {
    return SubCategory::firstOrCreate(
      [
        'slug' => Str::slug($title),
        'category_id' => $categoryId,
      ],
      ['title' => $title],
    );
  }

  public function deleteCategory(Category $category): void
  {
    $category->delete();
  }

  public function deleteSubCategory(SubCategory $subCategory): void
  {
    $subCategory->delete();
  }
}