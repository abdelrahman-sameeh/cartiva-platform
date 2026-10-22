<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Support\ProductVariantOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_search_form_opens_the_dedicated_search_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('action="'.route('products.search').'"', false)
            ->assertSee('Cativa')
            ->assertSee('تسوّق من متاجر مميزة')
            ->assertSee(route('stores.index'), false)
            ->assertSee('كل اللي بتدور عليه')
            ->assertSee('ابحث عن أي منتج')
            ->assertDontSee('printore', false)
            ->assertDontSee('ملابس تناسب كل يوم')
            ->assertDontSee('متجر متعدد البائعين')
            ->assertDontSee('بائعين مختلفين');
    }

    public function test_search_page_searches_active_products(): void
    {
        $seller = $this->createSeller();
        $matchingProduct = $seller->products()->create([
            'title' => 'Wireless Headphones',
            'description' => 'Comfortable audio product.',
            'cover_image' => '/storage/products/covers/headphones.png',
            'price' => 800,
            'discount_amount' => 50,
        ]);
        $seller->products()->create([
            'title' => 'Coffee Maker',
            'description' => 'Makes fresh coffee.',
            'cover_image' => '/storage/products/covers/coffee.png',
            'price' => 500,
            'discount_amount' => 0,
        ]);
        $seller->products()->create([
            'title' => 'Wireless Mouse',
            'description' => 'This inactive product must stay hidden.',
            'cover_image' => '/storage/products/covers/mouse.png',
            'price' => 300,
            'discount_amount' => 0,
            'is_active' => false,
        ]);

        $this->get(route('products.search', ['q' => 'Wireless']))
            ->assertOk()
            ->assertSee('Wireless Headphones')
            ->assertSee(route('stores.products.show', [$seller, $matchingProduct]), false)
            ->assertSee('stretched-link', false)
            ->assertDontSee('Coffee Maker')
            ->assertDontSee('Wireless Mouse');
    }

    public function test_search_page_can_search_by_attribute_and_category(): void
    {
        $seller = $this->createSeller();
        $category = Category::create(['title' => 'Electronics']);
        $subCategory = SubCategory::create([
            'title' => 'Phones',
            'category_id' => $category->id,
        ]);
        $product = $seller->products()->create([
            'title' => 'Creator Device',
            'description' => 'A device for professionals.',
            'cover_image' => '/storage/products/covers/device.png',
            'price' => 1200,
            'discount_amount' => 0,
        ]);
        $product->attributes()->create(['key' => 'color', 'value' => 'Midnight Black']);
        $product->attributes()->create(['key' => 'ram', 'value' => '16 GB']);
        $product->sub_categories()->attach($subCategory);
        $variantOptions = ['storage' => '256 GB'];
        $variant = $product->variants()->create([
            'stock' => 2,
            'option_signature' => ProductVariantOptions::signature($variantOptions),
        ]);
        $variant->options()->create(['key' => 'storage', 'value' => '256 GB']);

        $this->get(route('products.search', ['q' => 'Midnight']))
            ->assertOk()
            ->assertSee('Creator Device');

        $this->get(route('products.search', ['q' => 'Phones']))
            ->assertOk()
            ->assertSee('Creator Device');

        $this->get(route('products.search', ['q' => 'ram 16 GB']))
            ->assertOk()
            ->assertSee('Creator Device');

        $this->get(route('products.search', ['q' => 'storage 256 GB']))
            ->assertOk()
            ->assertSee('Creator Device');
    }

    public function test_search_page_combines_filters_and_sorts_by_discounted_price(): void
    {
        $seller = $this->createSeller();
        $category = Category::create(['title' => 'Fashion']);
        $subCategory = SubCategory::create([
            'title' => 'Shoes',
            'category_id' => $category->id,
        ]);
        $otherCategory = Category::create(['title' => 'Home']);
        $otherSubCategory = SubCategory::create([
            'title' => 'Kitchen',
            'category_id' => $otherCategory->id,
        ]);

        $matchingProduct = $seller->products()->create([
            'title' => 'Premium Shoes',
            'description' => 'Discounted shoes available in stock.',
            'cover_image' => '/storage/products/covers/premium-shoes.png',
            'price' => 500,
            'discount_amount' => 200,
        ]);
        $matchingProduct->variants()->create(['size' => 'M', 'color' => 'أسود', 'stock' => 4]);
        $matchingProduct->sub_categories()->attach($subCategory);

        $cheapProduct = $seller->products()->create([
            'title' => 'Basic Shoes',
            'description' => 'Shoes below the selected price range.',
            'cover_image' => '/storage/products/covers/basic-shoes.png',
            'price' => 150,
            'discount_amount' => 20,
        ]);
        $cheapProduct->variants()->create(['size' => 'M', 'color' => 'أسود', 'stock' => 5]);
        $cheapProduct->sub_categories()->attach($subCategory);

        $outOfStockProduct = $seller->products()->create([
            'title' => 'Limited Shoes',
            'description' => 'Shoes unavailable in stock.',
            'cover_image' => '/storage/products/covers/limited-shoes.png',
            'price' => 320,
            'discount_amount' => 20,
        ]);
        $outOfStockProduct->variants()->create(['size' => 'M', 'color' => 'أسود', 'stock' => 0]);
        $outOfStockProduct->sub_categories()->attach($subCategory);

        $otherProduct = $seller->products()->create([
            'title' => 'Kitchen Set',
            'description' => 'A product in another category.',
            'cover_image' => '/storage/products/covers/kitchen.png',
            'price' => 300,
            'discount_amount' => 50,
        ]);
        $otherProduct->variants()->create(['size' => 'M', 'color' => 'أسود', 'stock' => 2]);
        $otherProduct->sub_categories()->attach($otherSubCategory);

        $this->get(route('products.search', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('Premium Shoes');

        $this->get(route('products.search', ['min_price' => 250, 'max_price' => 350]))
            ->assertOk()
            ->assertSee('Premium Shoes');

        $this->get(route('products.search', ['max_price' => 150]))
            ->assertOk()
            ->assertSee('Basic Shoes')
            ->assertDontSee('Premium Shoes');

        $this->get(route('products.search', ['in_stock' => 1, 'on_sale' => 1]))
            ->assertOk()
            ->assertSee('Premium Shoes');

        $this->get(route('products.search', [
            'category_id' => $category->id,
            'min_price' => 250,
            'max_price' => 350,
            'in_stock' => 1,
            'on_sale' => 1,
        ]))
            ->assertOk()
            ->assertSee('Premium Shoes')
            ->assertDontSee('Basic Shoes')
            ->assertDontSee('Limited Shoes')
            ->assertDontSee('Kitchen Set');

        $this->get(route('products.search', ['sort' => 'price_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Premium Shoes', 'Limited Shoes', 'Kitchen Set', 'Basic Shoes']);

        $this->get(route('products.search', ['min_price' => 500, 'max_price' => 100]))
            ->assertRedirect(route('products.search'))
            ->assertSessionHasErrors('max_price');
    }

    public function test_guest_can_view_an_active_product_but_not_an_inactive_one(): void
    {
        $seller = $this->createSeller();
        $activeProduct = $seller->products()->create([
            'title' => 'Public Product',
            'description' => 'This product is visible to storefront visitors.',
            'cover_image' => '/storage/products/covers/public.png',
            'price' => 250,
            'discount_amount' => 25,
        ]);
        $activeProduct->attributes()->create(['key' => 'size', 'value' => 'Medium']);
        $activeProduct->variants()->create([
            'size' => 'M',
            'color' => 'أسود',
            'stock' => 7,
        ]);
        $inactiveProduct = $seller->products()->create([
            'title' => 'Hidden Product',
            'description' => 'This product is not publicly available.',
            'cover_image' => '/storage/products/covers/hidden.png',
            'price' => 100,
            'discount_amount' => 0,
            'is_active' => false,
        ]);

        $this->get(route('products.show', $activeProduct))
            ->assertRedirect(route('stores.products.show', [$seller, $activeProduct]));

        $this->get(route('stores.products.show', [$seller, $activeProduct]))
            ->assertOk()
            ->assertSee('Public Product')
            ->assertSee('225.00')
            ->assertSee('Medium')
            ->assertSee('M')
            ->assertSee('أسود');

        $this->get(route('products.show', $inactiveProduct))
            ->assertNotFound();
    }

    public function test_buyer_selects_a_generic_variant_before_adding_to_cart(): void
    {
        $seller = $this->createSeller();
        $buyer = User::create([
            'first_name' => 'مشتري',
            'last_name' => 'تجريبي',
            'email' => 'variant-buyer@example.com',
            'password' => 'password123',
        ]);
        $buyer->syncRoles(RoleName::USER);
        $product = $seller->products()->create([
            'title' => 'هاتف متعدد السعات',
            'description' => 'هاتف متاح بسعات وألوان مختلفة.',
            'price' => 250,
            'discount_amount' => 0,
        ]);
        foreach ([['128 GB', 'أسود', 3], ['256 GB', 'أزرق', 2]] as [$storage, $color, $stock]) {
            $options = ['storage' => $storage, 'color' => $color];
            $variant = $product->variants()->create([
                'stock' => $stock,
                'option_signature' => ProductVariantOptions::signature($options),
            ]);
            $variant->options()->createMany([
                ['key' => 'storage', 'value' => $storage],
                ['key' => 'color', 'value' => $color],
            ]);
        }

        $this->actingAs($buyer)
            ->get(route('stores.products.show', [$seller, $product]))
            ->assertOk()
            ->assertSee('id="variant-selector"', false)
            ->assertSee('name="product_variant_id"', false)
            ->assertSee('اختار المناسب ليك')
            ->assertSee('id="variant-summary"', false)
            ->assertSee('data-action="increase"', false)
            ->assertSee('128 GB')
            ->assertSee('256 GB')
            ->assertSee('أسود')
            ->assertSee('أزرق');
    }

    public function test_visitors_can_browse_and_search_stores_by_seller_name(): void
    {
        $seller = $this->createSeller();
        $seller->products()->create([
            'title' => 'Visible Store Product',
            'description' => 'Counts as an active product.',
            'price' => 100,
            'discount_amount' => 0,
        ]);
        $seller->products()->create([
            'title' => 'Hidden Store Product',
            'description' => 'Must not count as active.',
            'price' => 100,
            'discount_amount' => 0,
            'is_active' => false,
        ]);
        $otherSeller = User::create([
            'first_name' => 'Boutique',
            'last_name' => 'Owner',
            'email' => 'boutique-owner@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $buyer = User::create([
            'first_name' => 'Regular',
            'last_name' => 'Buyer',
            'email' => 'regular-buyer@example.com',
            'password' => 'password123',
        ]);

        $this->get(route('stores.index'))
            ->assertOk()
            ->assertSee('متجر Storefront Seller')
            ->assertSee('متجر Boutique Owner')
            ->assertSee('1 منتج متاح')
            ->assertSee(route('stores.show', $seller), false)
            ->assertDontSee('Regular Buyer');

        $this->get(route('stores.index', ['q' => 'Boutique']))
            ->assertOk()
            ->assertSee('متجر Boutique Owner')
            ->assertDontSee('متجر Storefront Seller')
            ->assertDontSee('Regular Buyer');

        $this->get(route('stores.index', ['q' => 'Storefront Seller']))
            ->assertOk()
            ->assertSee('متجر Storefront Seller')
            ->assertDontSee('متجر Boutique Owner');
    }

    public function test_seller_store_only_shows_its_own_active_products(): void
    {
        $seller = $this->createSeller();
        $otherSeller = User::create([
            'first_name' => 'Other',
            'last_name' => 'Seller',
            'email' => 'other-storefront-seller@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);

        $ownProduct = $seller->products()->create([
            'title' => 'Seller Exclusive Product',
            'description' => 'Visible only in this seller store.',
            'price' => 400,
            'discount_amount' => 0,
        ]);
        $seller->products()->create([
            'title' => 'Seller Hidden Product',
            'description' => 'Inactive product.',
            'price' => 200,
            'discount_amount' => 0,
            'is_active' => false,
        ]);
        $otherSeller->products()->create([
            'title' => 'Other Seller Product',
            'description' => 'Must not leak into this store.',
            'price' => 300,
            'discount_amount' => 0,
        ]);

        $this->get(route('stores.show', $seller))
            ->assertOk()
            ->assertSee('متجر Storefront Seller')
            ->assertSee('Seller Exclusive Product')
            ->assertSee(route('stores.search', $seller), false)
            ->assertSee(route('stores.products.show', [$seller, $ownProduct]), false)
            ->assertDontSee('Seller Hidden Product')
            ->assertDontSee('Other Seller Product');
    }

    public function test_seller_store_search_stays_scoped_to_that_seller(): void
    {
        $seller = $this->createSeller();
        $otherSeller = User::create([
            'first_name' => 'Search',
            'last_name' => 'Competitor',
            'email' => 'search-competitor@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);

        $seller->products()->create([
            'title' => 'Scoped Search Shirt',
            'description' => 'Owned by the selected seller.',
            'price' => 250,
            'discount_amount' => 0,
        ]);
        $otherSeller->products()->create([
            'title' => 'Scoped Search Jacket',
            'description' => 'Owned by another seller.',
            'price' => 450,
            'discount_amount' => 0,
        ]);

        $this->get(route('stores.search', [$seller, 'q' => 'Scoped Search']))
            ->assertOk()
            ->assertSee('Scoped Search Shirt')
            ->assertDontSee('Scoped Search Jacket')
            ->assertSee('كل النتائج والفلاتر محصورة في منتجات هذا البائع');
    }

    public function test_seller_store_rejects_products_owned_by_another_seller(): void
    {
        $seller = $this->createSeller();
        $otherSeller = User::create([
            'first_name' => 'Protected',
            'last_name' => 'Seller',
            'email' => 'protected-seller@example.com',
            'password' => 'password123',
        ])->syncRoles(RoleName::SELLER);
        $otherProduct = $otherSeller->products()->create([
            'title' => 'Protected Product',
            'description' => 'Owned by another seller.',
            'price' => 500,
            'discount_amount' => 0,
        ]);
        $buyer = User::create([
            'first_name' => 'Not',
            'last_name' => 'Seller',
            'email' => 'not-a-seller@example.com',
            'password' => 'password123',
        ]);

        $this->get(route('stores.products.show', [$seller, $otherProduct]))
            ->assertNotFound();

        $this->get(route('stores.show', $buyer))
            ->assertNotFound();
    }

    private function createSeller(): User
    {
        $seller = User::create([
            'first_name' => 'Storefront',
            'last_name' => 'Seller',
            'email' => 'storefront-seller@example.com',
            'password' => 'password123',
        ]);

        return $seller->syncRoles(RoleName::SELLER);
    }
}
