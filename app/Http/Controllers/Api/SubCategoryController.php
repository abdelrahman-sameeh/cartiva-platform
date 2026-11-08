<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Str;

class SubCategoryController
{
    public function sub_categories_by_category(Category $category)
    {
        $sub_categories = $category->subCategories()->get();

        return response()->json([
            'data' => $sub_categories,
        ]);
    }

    public function find_one(Request $request, string $id)
    {
        $sub_category = SubCategory::find($id);

        if (! $sub_category) {
            return response()->json([
                'message' => "sub category with this id {$id} not found",
            ], 404);
        }

        return response()->json($sub_category);
    }

    public function list(Request $request)
    {
        $limit = (int) $request->query('limit', 5);

        $sub_categories = SubCategory::paginate((int) $limit);

        return response()->json([
            'data' => $sub_categories->items(),
            'current_page' => $sub_categories->currentPage(),
            'last_page' => $sub_categories->lastPage(),
            'per_page' => $sub_categories->perPage(),
            'total' => $sub_categories->total(),
            'number_of_pages' => ceil($sub_categories->total() / $sub_categories->perPage()),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:50',
            'category_id' => 'required|exists:categories,id',
        ]);

        $sub_category = SubCategory::firstOrCreate(
            [
                'slug' => Str::slug($validated['title']),
                'category_id' => $validated['category_id'],
            ],
            $validated
        );

        return response()->json([
            'message' => 'success',
            'sub_category' => $sub_category,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $sub_category = SubCategory::find($id);

        if (! $sub_category) {
            return response()->json([
                'message' => "sub category with this id {$id} not found",
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:50',
            'category_id' => 'sometimes|integer|exists:categories,id',
        ]);

        $sub_category->update($validated);

        return response()->json($sub_category);
    }

    public function delete_one(SubCategory $sub_category)
    {
        $sub_category->delete();

        return response()->json(null, 204);
    }
}
