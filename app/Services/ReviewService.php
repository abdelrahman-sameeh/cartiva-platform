<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function createReview(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $product = Product::whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $user->hasPurchasedProduct($product->id)) {
                return [
                    'success' => false,
                    'message' => 'يجب عليك شراء المنتج أولاً قبل إضافة تقييم.',
                ];
            }

            $alreadyReviewed = $user->reviews()
                ->where('product_id', $product->id)
                ->exists();

            if ($alreadyReviewed) {
                return [
                    'success' => false,
                    'message' => 'لقد قمت بتقييم هذا المنتج من قبل.',
                ];
            }

            $user->reviews()->create([
                'product_id' => $product->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->refreshProductRating($product);

            return [
                'success' => true,
                'message' => 'تم إضافة تقييمك بنجاح، شكراً لمشاركة رأيك!',
            ];
        });
    }

    public function updateReview(Review $review, array $data): Review
    {
        return DB::transaction(function () use ($review, $data) {
            $product = Product::whereKey($review->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview = Review::whereKey($review->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview->update($data);
            $this->refreshProductRating($product);

            return $lockedReview->refresh();
        });
    }

    public function deleteReview(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $product = Product::whereKey($review->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            Review::whereKey($review->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();

            $this->refreshProductRating($product);
        });
    }

    private function refreshProductRating(Product $product): void
    {
        $rating = $product->reviews()
            ->selectRaw('COUNT(*) as aggregate_count, AVG(rating) as aggregate_average')
            ->first();

        $product->forceFill([
            'rating_count' => (int) $rating->aggregate_count,
            'rating_avg' => round((float) ($rating->aggregate_average ?? 0), 1),
        ])->save();
    }
}
