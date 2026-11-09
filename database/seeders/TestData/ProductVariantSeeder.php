<?php

namespace Database\Seeders\TestData;

use App\Models\Product;
use App\Support\ProductVariantOptions;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProductCatalog::products() as $definition) {
            $product = Product::query()->where('title', $definition['title'])->firstOrFail();

            foreach ($this->options($definition['category']) as $variantIndex => $values) {
                $variant = $product->variants()->create([
                    'sku' => sprintf('CTV-%03d-%02d', $definition['index'], $variantIndex + 1),
                    'stock' => 8 + (($definition['index'] + $variantIndex) % 18),
                    'option_signature' => ProductVariantOptions::signature($values),
                ]);
                $variant->options()->createMany(
                    collect($values)
                        ->map(fn (string $value, string $key): array => compact('key', 'value'))
                        ->values()
                        ->all()
                );
            }
        }
    }

    private function options(string $category): array
    {
        if (in_array($category, ['ملابس رجالي', 'ملابس حريمي', 'ملابس أطفال'], true)) {
            return [
                ['المقاس' => 'M', 'اللون' => 'اللون الأساسي'],
                ['المقاس' => 'L', 'اللون' => 'لون بديل'],
                ['المقاس' => 'XL', 'اللون' => 'اللون الأساسي'],
            ];
        }

        return [
            ['الإصدار' => 'قياسي', 'اللون' => 'اللون الأساسي'],
            ['الإصدار' => 'محسن', 'اللون' => 'لون بديل'],
        ];
    }
}
