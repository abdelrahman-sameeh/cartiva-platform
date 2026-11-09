<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::products() as $definition) {
            $product = Product::query()->where('title', $definition['title'])->firstOrFail();
            $product->attributes()->createMany([
                ['key' => 'الجودة', 'value' => 'خامات مختارة للاستخدام اليومي'],
                ['key' => 'الضمان', 'value' => $definition['category'] === 'إلكترونيات' ? '١٢ شهرًا' : 'ضمان عيوب الصناعة'],
                ['key' => 'بلد التوزيع', 'value' => 'مخصص للسوق المصري والعربي'],
            ]);
        }
    }
}
