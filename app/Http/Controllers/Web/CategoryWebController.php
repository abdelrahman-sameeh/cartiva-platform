<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryWebController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::with('sub_categories')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:50',
        ]);

        $this->categoryService->createCategory($validated['title']);

        return back()->with('success', 'تم إنشاء التصنيف بنجاح.');
    }

    public function storeSubCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:50',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $this->categoryService->createSubCategory($validated['title'], $validated['category_id']);

        return back()->with('success', 'تم إنشاء التصنيف الفرعي بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->deleteCategory($category);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'تم حذف التصنيف والتصنيفات الفرعية التابعة له.');
    }

    public function destroySubCategory(SubCategory $subCategory): RedirectResponse
    {
        $this->categoryService->deleteSubCategory($subCategory);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'تم حذف التصنيف الفرعي بنجاح.');
    }
}
