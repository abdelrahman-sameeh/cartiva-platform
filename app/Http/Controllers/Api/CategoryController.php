<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Str;

class CategoryController
{
    public function list(Request $request)
    {
        $categories = Category::all();

        return response()->json($categories);
    }

    public function create(Request $request)
    {
        $roles = [
            'title' => 'required|string|max:50',
        ];

        $validated = $request->validate($roles);

        $category = Category::firstOrCreate(
            ['slug' => Str::slug($validated['title'])],
            ['title' => $validated['title']],
        );

        return response()->json($category);
    }

    public function find_one(Request $request)
    {
        $category = Category::find($request->route('id'));

        if (! $category) {
            return response()->json(
                ['message' => 'Category not found'],
                404
            );
        }

        return response()->json($category, 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:50',
        ]);

        $categoryId = $request->route('id');

        $category = Category::find($categoryId);

        if (! $category) {
            return response()->json(
                ['message' => 'Category not found'],
                404
            );
        }

        $category->update([
            'title' => $validated['title'] ?? $category->title,
        ]);

        return response()->json($category, 200);

    }

    public function delete(Request $request)
    {

        $category = Category::find($request->route('id'));

        if (! $category) {
            return response()->json(
                ['message' => 'Category not found'],
                404
            );
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
