<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ProductAlreadyExistsException;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductPicture;
use App\Services\ProductService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController
{
    public function __construct(private readonly ProductService $productService) {}

    /** @var \App\Models\User */
    public function create(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->create($request->user(), $request->validated());
        } catch (ProductAlreadyExistsException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'data' => new ProductResource(
                    $exception->product->load('sub_categories', 'pictures', 'attributes', 'variants.options')
                ),
            ], 409);
        }

        return response()->json([
            'message' => 'product created successfully',
            'data' => new ProductResource(
                $product->load('sub_categories', 'pictures', 'attributes', 'variants.options')
            ),
        ], 201);

    }

    public function delete_one(int $id)
    {
        $user = Auth::user();
        $product = Product::find($id);
        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        if ($product->seller_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to access this product',
            ], 403); // forbidden
        }
        $product->delete();

        return response()->json(null, 204);
    }

    public function find_one(Request $request, int $id)
    {
        $user = $request->user();
        $product = $user->products()->find($id);

        if (! $product) {
            return response()->json([
                'message' => 'product not found',
            ], 404);
        }

        return response()->json(
            new ProductResource($product->load('attributes', 'sub_categories', 'pictures', 'variants.options')),
        );
    }

    public function find(Request $request)
    {
        $word = $request->query('word');
        $min_price = $request->query('min_price');
        $max_price = $request->query('max_price');
        $best_seller = $request->query('best_seller');
        $rating = $request->query('rating');
        $min_ratings = $request->query('min_ratings') ?? 0;
        $limit = $request->query('limit') ?? 10;
        $query = Product::query()
            ->with('attributes', 'sub_categories.category', 'pictures', 'variants.options')
            ->withSum('variants', 'stock');

        if (! is_null($word)) {
            $query->where(function ($q) use ($word) {
                $q->where('title', 'like', '%'.$word.'%')
                    ->orWhere('slug', 'like', '%'.$word.'%')
                    ->orWhere('description', 'like', '%'.$word.'%');
            });
        }
        if (! is_null($min_price)) {
            $query->where('price', '>=', $min_price);
        }
        if (! is_null($max_price)) {
            $query->where('price', '<=', $max_price);
        }
        if (! is_null($best_seller) && in_array($best_seller, ['1', '0'], true)) {
            $query->orderBy('sold_count', (int) $best_seller ? 'desc' : 'asc');
        }
        if (! is_null($rating) && in_array($rating, ['high', 'low'], true)) {
            $query
                ->where('rating_count', '>=', $min_ratings)
                ->orderBy('rating_avg', $rating == 'high' ? 'desc' : 'asc');
        }
        $products = $query->paginate($limit);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'prev_page' => $products->previousPageUrl(),
                'next_page' => $products->nextPageUrl(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $user = $request->user();
        $product = $user->products()->find($id);
        if (! $product) {
            return response()->json([
                'message' => 'product not found',
            ], 404);
        }
        try {
            $product = $this->productService->update($user, $product, $request->validated());
        } catch (ProductAlreadyExistsException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'data' => new ProductResource(
                    $exception->product->load('sub_categories', 'pictures', 'attributes', 'variants.options')
                ),
            ], 409);
        }

        return response()->json(new ProductResource(
            $product->load('sub_categories', 'pictures', 'attributes', 'variants.options')
        ));
    }

    public function add_product_pictures(Request $request, $id)
    {

        $product = $request->user()->products()->find($id);
        if (! $product) {
            return response()->json([
                'message' => 'product not found',
            ], 404);
        }

        $request->validate([
            'product_pictures' => 'nullable|array',
            'product_pictures.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('product_pictures')) {
            $product_pictures = [];
            foreach ($request->file('product_pictures') as $file) {
                $path = $file->store('products/gallery', 'public');
                $product_pictures[] = ['picture' => $path];
            }
            $product->pictures()->createMany($product_pictures);
        }

        return response()->json([
            'data' => $product->pictures()->get(),
        ], 201);
    }

    // Delete Single product picture
    public function delete_product_picture($id)
    {
        $picture_row = ProductPicture::with('product')->find($id);
        if (! $picture_row) {
            return response()->json(['message' => 'Product picture not found'], 404);
        }

        if (Auth::id() !== $picture_row->product->seller_id) {
            return response()->json([
                'message' => "You don't have access for this action",
                'status' => 'forbidden',
            ], 403);
        }

        // delete file
        $path = str_replace('/storage/', '', $picture_row->picture);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        $picture_row->delete();

        return response()->noContent();
    }

    // Delete Multiple product picture
    public function delete_product_pictures(Request $request)
    {

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $product_pictures = ProductPicture::with('product')->findMany($validated);

        foreach ($product_pictures as $pic) {
            if ($pic->product->seller_id === Auth::id()) {
                $path = str_replace('/storage/', '', $pic->picture);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $pic->delete();
            }
        }

        return response()->noContent();
    }
}
