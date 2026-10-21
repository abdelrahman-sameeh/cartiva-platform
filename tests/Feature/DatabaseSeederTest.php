<?php

namespace Tests\Feature;

use App\Models\Order\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }
}
