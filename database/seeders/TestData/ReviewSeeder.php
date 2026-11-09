<?php

namespace Database\Seeders\TestData;

use App\Models\Order\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $comments = [
            'المنتج مطابق للوصف وجودته كويسة جدًا.',
            'التغليف ممتاز والتوصيل كان منظم.',
            'الخامة عملية والسعر مناسب.',
            'تجربة شراء جيدة وسأكررها مرة أخرى.',
            'المنتج جميل لكن كنت أتمنى خيارات ألوان أكثر.',
        ];
        $reviewedPairs = [];

        OrderItem::query()
            ->whereHas('subOrder.order', fn ($query) => $query->where('status', 'completed'))
            ->with('subOrder.order')
            ->limit(30)
            ->get()
            ->each(function (OrderItem $item, int $index) use ($comments, &$reviewedPairs): void {
                $user = $item->subOrder->order->user;
                $key = $user->id.'-'.$item->product_id;

                if (! $item->product_id || isset($reviewedPairs[$key])) {
                    return;
                }

                $user->reviews()->create([
                    'product_id' => $item->product_id,
                    'rating' => 4 + ($index % 2),
                    'comment' => $comments[$index % count($comments)],
                ]);
                $reviewedPairs[$key] = true;
            });

        Product::query()->whereHas('reviews')->each(function (Product $product): void {
            $product->update([
                'rating_avg' => $product->reviews()->avg('rating'),
                'rating_count' => $product->reviews()->count(),
            ]);
        });
    }
}
