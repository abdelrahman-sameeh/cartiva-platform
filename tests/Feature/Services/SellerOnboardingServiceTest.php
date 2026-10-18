<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Account;
use Stripe\AccountLink;
use Tests\TestCase;

class SellerOnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_persists_a_connected_account_once(): void
    {
        $seller = User::factory()->create();
        $gateway = Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('createConnectedAccount')
            ->once()
            ->withArgs(fn (User $user, string $key) => $user->is($seller)
                && $key === "printora:seller-connect-account:{$seller->id}")
            ->andReturn($this->stripeAccount());

        $service = new SellerOnboardingService($gateway);
        $firstAccount = $service->accountFor($seller);
        $secondAccount = $service->accountFor($seller);

        $this->assertTrue($firstAccount->is($secondAccount));
        $this->assertDatabaseHas('seller_stripe_accounts', [
            'seller_id' => $seller->id,
            'stripe_account_id' => 'acct_seller',
            'details_submitted' => true,
            'charges_enabled' => true,
            'payouts_enabled' => false,
            'disabled_reason' => 'requirements.pending_verification',
        ]);
    }

    public function test_it_creates_a_fresh_onboarding_link_for_the_local_account(): void
    {
        $seller = User::factory()->create();
        $localAccount = $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_existing',
        ]);
        $gateway = Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('createConnectedAccount')->never();
        $gateway->shouldReceive('createOnboardingLink')
            ->once()
            ->with('acct_existing', 'https://example.test/refresh', 'https://example.test/return')
            ->andReturn(AccountLink::constructFrom([
                'url' => 'https://connect.stripe.test/onboarding',
            ]));

        $url = (new SellerOnboardingService($gateway))->onboardingUrl(
            $seller,
            'https://example.test/refresh',
            'https://example.test/return'
        );

        $this->assertSame('https://connect.stripe.test/onboarding', $url);
        $this->assertTrue($seller->stripeAccount->is($localAccount));
    }

    public function test_it_synchronizes_the_latest_account_status(): void
    {
        $seller = User::factory()->create();
        $localAccount = $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_seller',
        ]);
        $gateway = Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('retrieveConnectedAccount')
            ->once()
            ->with('acct_seller')
            ->andReturn($this->stripeAccount([
                'payouts_enabled' => true,
                'requirements' => [
                    'disabled_reason' => null,
                    'currently_due' => [],
                ],
            ]));

        $account = (new SellerOnboardingService($gateway))->sync($localAccount);

        $this->assertTrue($account->details_submitted);
        $this->assertTrue($account->charges_enabled);
        $this->assertTrue($account->payouts_enabled);
        $this->assertNull($account->disabled_reason);
        $this->assertSame([], $account->requirements_currently_due);
        $this->assertNotNull($account->last_synced_at);
    }

    private function stripeAccount(array $overrides = []): Account
    {
        return Account::constructFrom(array_replace([
            'id' => 'acct_seller',
            'details_submitted' => true,
            'charges_enabled' => true,
            'payouts_enabled' => false,
            'requirements' => [
                'disabled_reason' => 'requirements.pending_verification',
                'currently_due' => ['external_account'],
            ],
        ], $overrides));
    }
}
