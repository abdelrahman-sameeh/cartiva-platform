<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ProductAlreadyExistsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\SubCategory;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): View
    {
        return view('products.index', [
            'products' => $request->user()->products()->withSum('variants', 'stock')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'subCategories' => SubCategory::with('category')->orderBy('title')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            $product = $this->productService->create($request->user(), $request->validated());
        } catch (ProductAlreadyExistsException $exception) {
            return back()
                ->withErrors(['title' => 'لديك منتج آخر بنفس الاسم.'])
                ->withInput();
        }

        return redirect()
            ->route('seller.products.index')
            ->with('success', "تم إنشاء المنتج {$product->title} بنجاح.");
    }

    public function show(Request $request, Product $product): View
    {
        $product = $this->ownedProduct($request, $product);

        return view('products.show', [
            'product' => $product->load('attributes', 'sub_categories.category', 'pictures', 'variants.options'),
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $product = $this->ownedProduct($request, $product);

        return view('products.edit', [
            'product' => $product->load('attributes', 'sub_categories', 'pictures', 'variants.options'),
            'subCategories' => SubCategory::with('category')->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->ownedProduct($request, $product);

        try {
            $data = $request->validated();
            $data['attributes'] = $data['attributes'] ?? [];
            $product = $this->productService->update($request->user(), $product, $data);
        } catch (ProductAlreadyExistsException $exception) {
            return back()
                ->withErrors(['title' => 'لديك منتج آخر بنفس الاسم.'])
                ->withInput();
        }

        return redirect()
            ->route('seller.products.index')
            ->with('success', "تم تعديل المنتج {$product->title} بنجاح.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $product = $this->ownedProduct($request, $product);
        $title = $product->title;

        $product->delete();

        return redirect()
            ->route('seller.products.index')
            ->with('success', "تم حذف المنتج {$title} بنجاح.");
    }

    private function ownedProduct(Request $request, Product $product): Product
    {
        abort_unless($product->seller_id === $request->user()->id, 404);

        return $product;
    }
}
