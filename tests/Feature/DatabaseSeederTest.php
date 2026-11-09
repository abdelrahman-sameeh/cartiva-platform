<?php

namespace Tests\Feature;

use App\Models\Cart\CartItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductPicture;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_flexible_variants_and_order_snapshots(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, ProductVariant::query()->count());
        $this->assertSame(0, ProductVariant::query()->whereNull('option_signature')->count());
        $this->assertSame(
            ProductVariant::query()->count() * 2,
            ProductVariant::query()->withCount('options')->get()->sum('options_count')
        );
        $this->assertSame(0, OrderItem::query()->whereNull('variant_options')->count());
        $this->assertSame(50, User::query()->count());
        $this->assertSame(100, Product::query()->count());
        $this->assertSame(200, ProductPicture::query()->count());
        $this->assertSame(15, Order::query()->count());
        $this->assertSame(9, Payment::query()->count());
        $this->assertGreaterThanOrEqual(40, CartItem::query()->count());
        $this->assertGreaterThanOrEqual(20, Review::query()->count());
        $this->assertSame(60, Shipment::query()->count());
        $this->assertSame(12, Shipment::query()->where('cod_status', 'remitted')->count());

        Product::all()->each(function (Product $product) {
            $this->assertFileExists(public_path(ltrim($product->cover_image, '/')));
            $this->assertCount(2, $product->pictures);
            $product->pictures->each(function ($picture) {
                $this->assertFileExists(public_path(ltrim($picture->picture, '/')));
            });
        });

        User::all()->each(function (User $user) {
            $this->assertTrue(Hash::check('123456', $user->password));
        });
    }
}
