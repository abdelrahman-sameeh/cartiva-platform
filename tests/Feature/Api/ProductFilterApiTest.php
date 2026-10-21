<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_filtered_by_category_seller_and_effective_variant_price(): void
    {
        [$seller, $category, $subCategory] = $this->catalogContext('Electronics', 'Phones');
        [$otherSeller, $otherCategory, $otherSubCategory] = $this->catalogContext('Home', 'Kitchen');

        $matching = $this->product($seller, 'Flexible Phone', 1000, 100);
        $matching->sub_categories()->attach($subCategory);
        $this->variant($matching, ['storage' => '256 GB'], 3, 700);

        $wrongPrice = $this->product($seller, 'Expensive Phone', 600);
        $wrongPrice->sub_categories()->attach($subCategory);
        $this->variant($wrongPrice, ['storage' => '128 GB'], 3, 800);

        $wrongCategory = $this->product($otherSeller, 'Kitchen Device', 600);
        $wrongCategory->sub_categories()->attach($otherSubCategory);
        $this->variant($wrongCategory, [], 3, 600);

        $this->getJson('/api/products?'.http_build_query([
            'category_ids' => [$category->id],
            'seller_ids' => [$seller->id],
            'min_price' => 550,
            'max_price' => 650,
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Flexible Phone');

        $this->assertNotSame($category->id, $otherCategory->id);
    }

    public function test_dynamic_attributes_support_or_values_and_numeric_ranges(): void
    {
        [$seller] = $this->catalogContext('Devices', 'Laptops');
        $matching = $this->product($seller, 'Creator Laptop', 2000);
        $this->variant($matching, [], 2);
        $matching->attributes()->createMany([
            ['key' => 'brand', 'value' => 'Cativa'],
            ['key' => 'ram', 'value' => '16 GB', 'numeric_value' => 16, 'unit' => 'GB'],
        ]);

        $other = $this->product($seller, 'Basic Laptop', 1200);
        $this->variant($other, [], 2);
        $other->attributes()->createMany([
            ['key' => 'brand', 'value' => 'Other'],
            ['key' => 'ram', 'value' => '8 GB', 'numeric_value' => 8, 'unit' => 'GB'],
        ]);

        $this->getJson('/api/products?'.http_build_query([
            'attributes' => ['brand' => ['Cativa', 'Alternative']],
            'attribute_ranges' => ['ram' => ['min' => 12, 'max' => 32, 'unit' => 'GB']],
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Creator Laptop');
    }

    public function test_option_and_stock_filters_must_match_the_same_variant(): void
    {
        [$seller] = $this->catalogContext('Fashion', 'Shirts');
        $matching = $this->product($seller, 'Matching Shirt', 300);
        $this->variant($matching, ['color' => 'Black', 'size' => 'M'], 2);

        $split = $this->product($seller, 'Split Shirt', 300);
        $this->variant($split, ['color' => 'Black', 'size' => 'M'], 0);
        $this->variant($split, ['color' => 'Black', 'size' => 'L'], 4);
        $this->variant($split, ['color' => 'Blue', 'size' => 'M'], 4);

        $this->getJson('/api/products?'.http_build_query([
            'options' => [
                'color' => ['Black'],
                'size' => ['M'],
            ],
            'in_stock' => 1,
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Matching Shirt');
    }

    public function test_search_sort_and_pagination_are_supported(): void
    {
        [$seller] = $this->catalogContext('Books', 'Programming');
        $expensive = $this->product($seller, 'Laravel Advanced', 500);
        $this->variant($expensive, [], 2);
        $cheap = $this->product($seller, 'Laravel Basics', 200);
        $this->variant($cheap, [], 2);
        $unrelated = $this->product($seller, 'Cooking Guide', 100);
        $this->variant($unrelated, [], 2);

        $this->getJson('/api/products?'.http_build_query([
            'search' => 'Laravel',
            'sort' => 'price_asc',
            'per_page' => 1,
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Basics')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    /** @return array{User, Category, SubCategory} */
    private function catalogContext(string $categoryTitle, string $subCategoryTitle): array
    {
        $seller = User::create([
            'first_name' => $categoryTitle,
            'last_name' => 'Seller',
            'email' => strtolower($categoryTitle).uniqid().'@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $category = Category::create(['title' => $categoryTitle]);
        $subCategory = SubCategory::create([
            'title' => $subCategoryTitle,
            'category_id' => $category->id,
        ]);

        return [$seller, $category, $subCategory];
    }

    private function product(User $seller, string $title, float $price, float $discount = 0): Product
    {
        return $seller->products()->create([
            'title' => $title,
            'description' => "Description for {$title}.",
            'price' => $price,
            'discount_amount' => $discount,
        ]);
    }

    /** @param array<string, string> $options */
    private function variant(Product $product, array $options, int $stock, ?float $price = null): void
    {
        $variant = $product->variants()->create([
            'price' => $price,
            'stock' => $stock,
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
