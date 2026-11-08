<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'تيشيرت أسود كلاسيك' => [
                ['الخامة', 'قطن ١٠٠٪'],
                ['القَصّة', 'كلاسيكية مريحة'],
                ['تعليمات الغسيل', 'غسيل بارد مع ألوان مشابهة'],
            ],
            'تيشيرت أسود أوفرسايز' => [
                ['الخامة', 'قطن ثقيل'],
                ['القَصّة', 'أوفرسايز'],
                ['تعليمات الغسيل', 'غسيل مقلوب على ٣٠ درجة'],
            ],
            'جاكيت جينز حريمي' => [
                ['الخامة', 'جينز قطني'],
                ['القَصّة', 'مريحة'],
                ['الموسم', 'خريف وربيع'],
            ],
            'فستان كتان موف' => [
                ['الخامة', 'كتان مخلوط'],
                ['الطول', 'طويل'],
                ['القَصّة', 'واسعة من الأسفل'],
            ],
            'جاكيت هودي حضري' => [
                ['الخامة', 'قطن مبطن'],
                ['القَصّة', 'كاجوال'],
                ['الموسم', 'شتاء'],
            ],
            'شورت أطفال سماوي' => [
                ['الخامة', 'قطن خفيف'],
                ['الوسط', 'مطاطي'],
                ['تعليمات الغسيل', 'غسيل آلي على ٣٠ درجة'],
            ],
        ];

        foreach ($attributes as $productTitle => $productAttributes) {
            $product = Product::query()->where('title', $productTitle)->firstOrFail();

            foreach ($productAttributes as [$key, $value]) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        }
    }
}
