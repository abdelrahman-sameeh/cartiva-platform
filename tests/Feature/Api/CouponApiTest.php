<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_a_coupon_through_the_api(): void
    {
        $seller = $this->createSeller();
        Sanctum::actingAs($seller);

        $this->postJson('/api/coupons', [
            'code' => 'API15',
            'percentage' => 15,
            'max_usage' => 30,
            'expire_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('code', 'API15')
            ->assertJsonPath('seller_id', $seller->id);
    }

    private function createSeller(): User
    {
        $seller = User::create([
            'first_name' => 'بائع',
            'last_name' => 'تجريبي',
            'email' => 'api-seller@example.com',
            'password' => 'password123',
        ]);
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }
}
