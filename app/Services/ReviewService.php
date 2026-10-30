<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use DB;

class ReviewService
{
    public function createReview(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {

            $product = Product::whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Check purchase
            if (! $user->hasPurchasedProduct($product->id)) {
                return [
                    'success' => false,
                    'message' => 'يجب عليك شراء المنتج أولاً قبل إضافة تقييم.',
                ];
            }

            // Check duplicate review
            $alreadyReviewed = $user->reviews()
                ->where('product_id', $product->id)
                ->exists();

            if ($alreadyReviewed) {
                return [
                    'success' => false,
                    'message' => 'لقد قمت بتقييم هذا المنتج من قبل.',
                ];
            }

            // Create review
            $user->reviews()->create([
                'product_id' => $product->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            // Update rating statistics
            $product->rating_count = $product->reviews()->count();
            $product->rating_avg = $product->reviews()->avg('rating');
            $product->save();

            return [
                'success' => true,
                'message' => 'تم إضافة تقييمك بنجاح، شكراً لمشاركة رأيك!',
            ];
        });
    }
}
