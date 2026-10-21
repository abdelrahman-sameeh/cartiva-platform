<?php

namespace App\Services;

use App\Exceptions\ProductAlreadyExistsException;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductVariantOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function create(User $seller, array $data): Product
    {
        $existingProduct = $seller->products()
            ->whereSlug(Str::slug($data['title']))
            ->first();

        if ($existingProduct) {
            throw new ProductAlreadyExistsException($existingProduct);
        }

        return DB::transaction(function () use ($seller, $data) {
            $product = $seller->products()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'price' => $data['price'],
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            /** @var UploadedFile $coverImage */
            $coverPath = $data['cover_image']->store('products/covers', 'public');
            $product->update(['cover_image' => Storage::url($coverPath)]);

            if (! empty($data['product_pictures'])) {
                $product->pictures()->createMany(
                    collect($data['product_pictures'])
                        ->map(fn (UploadedFile $image) => [
                            'picture' => Storage::url($image->store('products/gallery', 'public')),
                        ])
                        ->all()
                );
            }

            $product->sub_categories()->sync($data['sub_categories'] ?? []);
            $this->syncVariants($product, $data['variants']);

            foreach ($data['attributes'] ?? [] as $attribute) {
                $product->attributes()->create([
                    'key' => mb_strtolower(trim($attribute['key'])),
                    'value' => trim($attribute['value']),
                    'numeric_value' => $attribute['numeric_value'] ?? null,
                    'unit' => isset($attribute['unit']) ? trim($attribute['unit']) ?: null : null,
                ]);
            }

            return $product;
        });
    }

    public function update(User $seller, Product $product, array $data): Product
    {
        if (array_key_exists('discount_amount', $data) && is_null($data['discount_amount'])) {
            $data['discount_amount'] = 0;
        }

        $duplicateProduct = $seller->products()
            ->whereSlug(Str::slug($data['title'] ?? $product->title))
            ->where('id', '!=', $product->id)
            ->first();

        if ($duplicateProduct) {
            throw new ProductAlreadyExistsException($duplicateProduct);
        }

        $oldCoverPath = null;

        if (! empty($data['cover_image'])) {
            /** @var UploadedFile $coverImage */
            $coverImage = $data['cover_image'];
            $coverPath = $coverImage->store('products/covers', 'public');
            $data['cover_image'] = Storage::url($coverPath);
            $oldCoverPath = str_replace('/storage/', '', $product->cover_image);
        }

        $product = DB::transaction(function () use ($product, $data) {
            $product->update(collect($data)->only([
                'title',
                'description',
                'price',
                'discount_amount',
                'cover_image',
            ])->all());

            if (array_key_exists('variants', $data)) {
                $this->syncVariants($product, $data['variants']);
            }

            if (array_key_exists('sub_categories', $data)) {
                $product->sub_categories()->sync($data['sub_categories']);
            }

            if (array_key_exists('attributes', $data)) {
                $product->attributes()->delete();

                foreach ($data['attributes'] ?? [] as $attribute) {
                    $product->attributes()->create([
                        'key' => mb_strtolower(trim($attribute['key'])),
                        'value' => trim($attribute['value']),
                        'numeric_value' => $attribute['numeric_value'] ?? null,
                        'unit' => isset($attribute['unit']) ? trim($attribute['unit']) ?: null : null,
                    ]);
                }
            }

            if (! empty($data['product_pictures'])) {
                $product->pictures()->createMany(
                    collect($data['product_pictures'])
                        ->map(fn (UploadedFile $image) => [
                            'picture' => Storage::url($image->store('products/gallery', 'public')),
                        ])
                        ->all()
                );
            }

            return $product->refresh();
        });

        if ($oldCoverPath) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        return $product;
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $existingVariants = $product->variants()->get()->keyBy('id');
        $requestedIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($requestedIds->diff($existingVariants->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'أحد اختيارات المنتج لا يخص هذا المنتج.',
            ]);
        }

        $product->variants()->whereNotIn('id', $requestedIds)->delete();

        if ($requestedIds->isNotEmpty()) {
            $product->variants()->whereIn('id', $requestedIds)->update(['option_signature' => null]);
        }

        foreach ($variants as $variantData) {
            $options = ProductVariantOptions::normalize($variantData['options'] ?? []);
            $variantValues = [
                'sku' => isset($variantData['sku']) ? trim($variantData['sku']) ?: null : null,
                'size' => $options['size'] ?? null,
                'color' => $options['color'] ?? null,
                'price' => $this->priceOverride($variantData['price'] ?? null, $product->price),
                'stock' => $variantData['stock'],
                'option_signature' => ProductVariantOptions::signature($options),
            ];

            $variant = isset($variantData['id'])
                ? $existingVariants->get((int) $variantData['id'])
                : $product->variants()->make();
            $variant->fill($variantValues)->save();
            $variant->options()->delete();
            $variant->options()->createMany(
                collect($options)
                    ->map(fn (string $value, string $key): array => compact('key', 'value'))
                    ->values()
                    ->all()
            );
        }
    }

    private function priceOverride(mixed $price, mixed $basePrice): ?string
    {
        if ($price === null || $price === '') {
            return null;
        }

        $normalizedPrice = number_format((float) $price, 2, '.', '');
        $normalizedBasePrice = number_format((float) $basePrice, 2, '.', '');

        return $normalizedPrice === $normalizedBasePrice ? null : $normalizedPrice;
    }
}
