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
            'card_payments_status' => 'active',
            'payouts_status' => 'pending',
            'transfers_status' => 'active',
            'requirements' => [['description' => 'Add a bank account']],
            'last_synced_at' => $syncedAt,
        ]);

        $seller->refresh();

        $this->assertTrue($seller->stripeAccount->is($account));
        $this->assertSame('active', $account->card_payments_status);
        $this->assertSame('pending', $account->payouts_status);
        $this->assertSame('active', $account->transfers_status);
        $this->assertSame([['description' => 'Add a bank account']], $account->requirements);
        $this->assertFalse($account->isReady());
        $this->assertTrue($account->hasOutstandingRequirements());
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
