<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_sellers_can_open_coupon_management(): void
    {
        $buyer = $this->createUser('buyer@example.com');

        $this->actingAs($buyer)
            ->get(route('seller.coupons.index'))
            ->assertForbidden();
    }

    public function test_seller_can_create_a_coupon(): void
    {
        $seller = $this->createSeller('seller@example.com');

        $this->actingAs($seller)
            ->post(route('seller.coupons.store'), [
                'code' => 'خصم_الصيف_20',
                'percentage' => 20,
                'max_usage' => 50,
                'expire_date' => now()->addMonth()->toDateString(),
                'is_active' => 1,
            ])
            ->assertRedirect(route('seller.coupons.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'seller_id' => $seller->id,
            'code' => 'خصم_الصيف_20',
            'percentage' => 20,
            'max_usage' => 50,
            'is_active' => true,
        ]);
    }

    public function test_seller_can_update_and_delete_their_coupon(): void
    {
        $seller = $this->createSeller('seller@example.com');
        $coupon = $this->createCoupon($seller, 'OLD10');

        $this->actingAs($seller)
            ->put(route('seller.coupons.update', $coupon), [
                'code' => 'NEW25',
                'percentage' => 25,
                'max_usage' => 20,
                'expire_date' => now()->addMonths(2)->toDateString(),
                'is_active' => 0,
            ])
            ->assertRedirect(route('seller.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW25',
            'percentage' => 25,
            'is_active' => false,
        ]);

        $this->actingAs($seller)
            ->delete(route('seller.coupons.destroy', $coupon))
            ->assertRedirect(route('seller.coupons.index'));

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_seller_cannot_manage_another_sellers_coupon(): void
    {
        $owner = $this->createSeller('owner@example.com');
        $otherSeller = $this->createSeller('other@example.com');
        $coupon = $this->createCoupon($owner, 'PRIVATE10');

        $this->actingAs($otherSeller)
            ->get(route('seller.coupons.edit', $coupon))
            ->assertNotFound();

        $this->actingAs($otherSeller)
            ->delete(route('seller.coupons.destroy', $coupon))
            ->assertNotFound();

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_coupon_code_is_unique_per_seller(): void
    {
        $firstSeller = $this->createSeller('first@example.com');
        $secondSeller = $this->createSeller('second@example.com');
        $this->createCoupon($firstSeller, 'SAVE10');

        $couponData = [
            'code' => 'SAVE10',
            'percentage' => 10,
            'max_usage' => 10,
            'expire_date' => now()->addMonth()->toDateString(),
            'is_active' => 1,
        ];

        $this->actingAs($firstSeller)
            ->post(route('seller.coupons.store'), $couponData)
            ->assertSessionHasErrors('code');

        $this->actingAs($secondSeller)
            ->post(route('seller.coupons.store'), $couponData)
            ->assertRedirect(route('seller.coupons.index'));

        $this->assertDatabaseCount('coupons', 2);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function createSeller(string $email): User
    {
        $seller = $this->createUser($email);
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }

    private function createCoupon(User $seller, string $code): Coupon
    {
        return Coupon::create([
            'code' => $code,
            'percentage' => 10,
            'expire_date' => now()->addMonth()->toDateString(),
            'max_usage' => 10,
            'used_count' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
    }
}
