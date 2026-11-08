<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Support\ProductVariantOptions;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        $variants = [
            'تيشيرت أسود كلاسيك' => [
                ['size' => 'S', 'color' => 'أسود', 'stock' => 12],
                ['size' => 'M', 'color' => 'أسود', 'stock' => 18],
                ['size' => 'L', 'color' => 'أسود', 'stock' => 15],
                ['size' => 'XL', 'color' => 'أسود', 'stock' => 7],
            ],
            'تيشيرت أسود أوفرسايز' => [
                ['size' => 'M', 'color' => 'أسود', 'stock' => 10],
                ['size' => 'L', 'color' => 'أسود', 'stock' => 14],
                ['size' => 'XL', 'color' => 'أسود', 'stock' => 6],
                ['size' => 'M', 'color' => 'فحمي', 'stock' => 8],
                ['size' => 'L', 'color' => 'فحمي', 'stock' => 9],
                ['size' => 'XL', 'color' => 'فحمي', 'stock' => 4],
            ],
            'جاكيت جينز حريمي' => [
                ['size' => 'S', 'color' => 'أزرق', 'stock' => 5],
                ['size' => 'M', 'color' => 'أزرق', 'stock' => 9],
                ['size' => 'L', 'color' => 'أزرق', 'stock' => 6],
                ['size' => 'S', 'color' => 'أسود', 'stock' => 4],
                ['size' => 'M', 'color' => 'أسود', 'stock' => 7],
                ['size' => 'L', 'color' => 'أسود', 'stock' => 3],
            ],
            'فستان كتان موف' => [
                ['size' => 'S', 'color' => 'موف', 'stock' => 4],
                ['size' => 'M', 'color' => 'موف', 'stock' => 8],
                ['size' => 'L', 'color' => 'موف', 'stock' => 5],
                ['size' => 'S', 'color' => 'بيج', 'stock' => 3],
                ['size' => 'M', 'color' => 'بيج', 'stock' => 6],
                ['size' => 'L', 'color' => 'بيج', 'stock' => 2],
            ],
            'جاكيت هودي حضري' => [
                ['size' => 'M', 'color' => 'بنفسجي', 'stock' => 7],
                ['size' => 'L', 'color' => 'بنفسجي', 'stock' => 10],
                ['size' => 'XL', 'color' => 'بنفسجي', 'stock' => 4],
                ['size' => 'M', 'color' => 'أسود', 'stock' => 9],
                ['size' => 'L', 'color' => 'أسود', 'stock' => 11],
                ['size' => 'XL', 'color' => 'أسود', 'stock' => 5],
            ],
            'شورت أطفال سماوي' => [
                ['size' => '4 سنوات', 'color' => 'سماوي', 'stock' => 8],
                ['size' => '6 سنوات', 'color' => 'سماوي', 'stock' => 12],
                ['size' => '8 سنوات', 'color' => 'سماوي', 'stock' => 10],
                ['size' => '10 سنوات', 'color' => 'سماوي', 'stock' => 6],
                ['size' => '4 سنوات', 'color' => 'بيج', 'stock' => 5],
                ['size' => '6 سنوات', 'color' => 'بيج', 'stock' => 7],
                ['size' => '8 سنوات', 'color' => 'بيج', 'stock' => 4],
                ['size' => '10 سنوات', 'color' => 'بيج', 'stock' => 3],
            ],
        ];

        foreach ($variants as $productTitle => $rows) {
            $product = Product::query()->where('title', $productTitle)->firstOrFail();

            foreach ($rows as $row) {
                $options = [
                    'size' => $row['size'],
                    'color' => $row['color'],
                ];
                $variant = $product->variants()->create([
                    'stock' => $row['stock'],
                    'option_signature' => ProductVariantOptions::signature($options),
                ]);
                $variant->options()->createMany(
                    collect($options)
                        ->map(fn (string $value, string $key): array => compact('key', 'value'))
                        ->values()
                        ->all()
                );
            }
        }
    }
}
