<?php

namespace Tests\Feature\Services;

use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\V2\Core\Account;
use Stripe\V2\Core\AccountLink;
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
                && $key === "cartiva:seller-connect-account:express-recipient-v1:{$seller->id}")
            ->andReturn($this->stripeAccount());

        $service = new SellerOnboardingService($gateway);
        $firstAccount = $service->accountFor($seller);
        $secondAccount = $service->accountFor($seller);
        $thirdAccount = $service->accountFor($seller);

        $this->assertTrue($firstAccount->is($secondAccount));
        $this->assertTrue($firstAccount->is($thirdAccount));
        $this->assertDatabaseHas('seller_stripe_accounts', [
            'seller_id' => $seller->id,
            'stripe_account_id' => 'acct_seller',
            'onboarding_status' => SellerStripeAccount::STATUS_READY,
            'payouts_status' => 'pending',
            'transfers_status' => 'active',
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
            ->andReturn($this->stripeAccount('active', []));

        $account = (new SellerOnboardingService($gateway))->sync($localAccount);

        $this->assertSame(SellerStripeAccount::STATUS_READY, $account->onboarding_status);
        $this->assertSame('active', $account->payouts_status);
        $this->assertSame('active', $account->transfers_status);
        $this->assertSame([], $account->requirements_due);
        $this->assertTrue($account->isReady());
        $this->assertNotNull($account->last_synced_at);
    }

    public function test_it_marks_an_account_as_action_required_when_stripe_needs_user_input(): void
    {
        $seller = User::factory()->create();
        $localAccount = $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_seller',
        ]);
        $stripeAccount = $this->stripeAccount('restricted');
        $stripeAccount->configuration->recipient->capabilities->stripe_balance->stripe_transfers->status = 'restricted';
        $stripeAccount->configuration->recipient->capabilities->stripe_balance->stripe_transfers->status_details = [
            \Stripe\StripeObject::constructFrom(['code' => 'requirements.past_due']),
        ];
        $gateway = Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('retrieveConnectedAccount')->once()->andReturn($stripeAccount);

        $account = (new SellerOnboardingService($gateway))->sync($localAccount);

        $this->assertSame(SellerStripeAccount::STATUS_ACTION_REQUIRED, $account->onboarding_status);
        $this->assertSame('requirements.past_due', $account->disabled_reason);
        $this->assertTrue($account->hasOutstandingRequirements());
        $this->assertFalse($account->isReady());
    }

    public function test_a_stripe_failure_does_not_create_a_local_account(): void
    {
        $seller = User::factory()->create();
        $gateway = Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('createConnectedAccount')
            ->once()
            ->andThrow(new \Stripe\Exception\ApiConnectionException('Stripe is unavailable.'));

        try {
            (new SellerOnboardingService($gateway))->accountFor($seller);
            $this->fail('The Stripe exception was not thrown.');
        } catch (\Stripe\Exception\ApiConnectionException) {
            $this->assertDatabaseMissing('seller_stripe_accounts', [
                'seller_id' => $seller->id,
            ]);
        }
    }

    private function stripeAccount(
        string $payoutsStatus = 'pending',
        array $requirements = [[
            'description' => 'Add a bank account',
            'awaiting_action_from' => 'user',
            'minimum_deadline' => ['status' => 'currently_due'],
        ]]
    ): Account {
        return Account::constructFrom([
            'id' => 'acct_seller',
            'configuration' => [
                'customer' => ['applied' => true],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'payouts' => ['status' => $payoutsStatus],
                            'stripe_transfers' => ['status' => 'active'],
                        ],
                    ],
                ],
            ],
            'requirements' => ['entries' => $requirements],
        ]);
    }
}
