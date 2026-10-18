<?php

namespace Tests\Feature\Webhooks;

use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeConnectWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_example',
            'services.stripe.connect.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    public function test_a_signed_account_event_synchronizes_the_local_account(): void
    {
        $account = User::factory()->create()->stripeAccount()->create([
            'stripe_account_id' => 'acct_seller',
        ]);
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')
            ->once()
            ->with(Mockery::on(fn (SellerStripeAccount $candidate) => $candidate->is($account)))
            ->andReturn($account);
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->postSignedEvent(
            'v2.core.account[configuration.merchant].capability_status_updated',
            'acct_seller'
        )->assertNoContent();
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')->never();
        $this->app->instance(SellerOnboardingService::class, $service);

        $payload = $this->eventPayload('v2.core.account.updated', 'acct_seller');

        $this->call('POST', route('webhooks.stripe.connect'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid',
        ], $payload)->assertStatus(400);
    }

    public function test_irrelevant_events_are_acknowledged_without_synchronizing(): void
    {
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')->never();
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->postSignedEvent('v2.core.account_link.returned', 'acct_seller')
            ->assertNoContent();
    }

    public function test_events_for_unknown_accounts_are_acknowledged(): void
    {
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')->never();
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->postSignedEvent('v2.core.account.updated', 'acct_unknown')
            ->assertNoContent();
    }

    private function postSignedEvent(string $type, string $accountId)
    {
        $payload = $this->eventPayload($type, $accountId);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', route('webhooks.stripe.connect'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    private function eventPayload(string $type, string $accountId): string
    {
        return json_encode([
            'id' => 'evt_test',
            'object' => 'v2.core.event',
            'type' => $type,
            'created' => now()->toIso8601String(),
            'livemode' => false,
            'related_object' => [
                'id' => $accountId,
                'type' => 'v2.core.account',
                'url' => "/v2/core/accounts/{$accountId}",
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
