<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->where('email', 'printore-seller@example.test')->firstOrFail();

        $products = [
            [
                'title' => 'تيشيرت أسود كلاسيك',
                'description' => 'تيشيرت رجالي أسود من القطن الناعم بقصة كلاسيكية مريحة، مناسب للاستخدام اليومي ويمكن تنسيقه بسهولة مع الجينز أو الملابس الرياضية.',
                'cover_image' => '/images/products/classic-black-tshirt.webp',
                'price' => 449.00,
                'discount_amount' => 50.00,
            ],
            [
                'title' => 'تيشيرت أسود أوفرسايز',
                'description' => 'تيشيرت أوفرسايز بخامة قطنية ثقيلة نسبيًا وقصة واسعة عصرية، مناسب للخروجات والإطلالات الكاجوال.',
                'cover_image' => '/images/products/oversized-tshirt.webp',
                'price' => 549.00,
                'discount_amount' => 75.00,
            ],
            [
                'title' => 'جاكيت جينز حريمي',
                'description' => 'جاكيت جينز حريمي بقصة مريحة وتفاصيل عملية، مناسب للجو المعتدل ويمكن ارتداؤه فوق التيشيرتات والفساتين.',
                'cover_image' => '/images/products/denim-jacket.webp',
                'price' => 1299.00,
                'discount_amount' => 200.00,
            ],
            [
                'title' => 'فستان كتان موف',
                'description' => 'فستان كتان طويل وخفيف بتصميم بسيط وأنيق، مناسب للخروجات النهارية والأجواء الصيفية.',
                'cover_image' => '/images/products/linen-dress.webp',
                'price' => 1499.00,
                'discount_amount' => 150.00,
            ],
            [
                'title' => 'جاكيت هودي حضري',
                'description' => 'جاكيت شبابي بغطاء رأس وتصميم حضري عملي، يمنح الدفء ويكمل الإطلالات الكاجوال في الأيام الباردة.',
                'cover_image' => '/images/products/urban-hoodie.webp',
                'price' => 1099.00,
                'discount_amount' => 100.00,
            ],
            [
                'title' => 'شورت أطفال سماوي',
                'description' => 'شورت أطفال خفيف بوسط مطاطي مريح وخامة لطيفة على البشرة، مناسب للعب والحركة طوال اليوم.',
                'cover_image' => '/images/products/kids-denim-shorts.webp',
                'price' => 349.00,
                'discount_amount' => 0,
            ],
        ];

        foreach ($products as $product) {
            Product::create([
                ...$product,
                'seller_id' => $seller->id,
                'is_active' => true,
            ]);
        }
    }
}
