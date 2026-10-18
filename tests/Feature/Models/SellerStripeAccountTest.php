<?php

namespace Tests\Feature\Models;

use App\Models\SellerStripeAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerStripeAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_has_one_stripe_account_with_provider_status_casts(): void
    {
        $seller = User::factory()->create();
        $syncedAt = now()->startOfSecond();

        $account = $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_seller_one',
            'details_submitted' => true,
            'charges_enabled' => true,
            'payouts_enabled' => false,
            'requirements_currently_due' => ['external_account'],
            'last_synced_at' => $syncedAt,
        ]);

        $seller->refresh();

        $this->assertTrue($seller->stripeAccount->is($account));
        $this->assertTrue($account->details_submitted);
        $this->assertTrue($account->charges_enabled);
        $this->assertFalse($account->payouts_enabled);
        $this->assertSame(['external_account'], $account->requirements_currently_due);
        $this->assertTrue($account->last_synced_at->equalTo($syncedAt));
    }

    public function test_a_seller_cannot_have_more_than_one_stripe_account(): void
    {
        $seller = User::factory()->create();

        SellerStripeAccount::create([
            'seller_id' => $seller->id,
            'stripe_account_id' => 'acct_first',
        ]);

        $this->expectException(QueryException::class);

        SellerStripeAccount::create([
            'seller_id' => $seller->id,
            'stripe_account_id' => 'acct_second',
        ]);
    }

    public function test_a_stripe_account_id_cannot_be_assigned_to_multiple_sellers(): void
    {
        $firstSeller = User::factory()->create();
        $secondSeller = User::factory()->create();

        SellerStripeAccount::create([
            'seller_id' => $firstSeller->id,
            'stripe_account_id' => 'acct_shared',
        ]);

        $this->expectException(QueryException::class);

        SellerStripeAccount::create([
            'seller_id' => $secondSeller->id,
            'stripe_account_id' => 'acct_shared',
        ]);
    }
}
