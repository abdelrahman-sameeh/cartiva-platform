<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeConnectGatewayTest extends TestCase
{
    private ClientInterface $originalHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalHttpClient = ApiRequestor::httpClient();
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient($this->originalHttpClient);

        parent::tearDown();
    }

    public function test_it_uses_accounts_v2_for_account_onboarding(): void
    {
        config(['services.stripe.connect.country' => 'us']);
        $httpClient = new RecordingStripeHttpClient;
        ApiRequestor::setHttpClient($httpClient);
        $gateway = new StripeConnectGateway(new StripeClient('sk_test_example'));
        $seller = (new User)->forceFill([
            'id' => 42,
            'first_name' => 'Seller',
            'last_name' => 'Example',
            'email' => 'seller@example.test',
        ]);

        $account = $gateway->createConnectedAccount($seller, 'account-key');
        $link = $gateway->createOnboardingLink(
            $account->id,
            'https://example.test/refresh',
            'https://example.test/return'
        );
        $retrieved = $gateway->retrieveConnectedAccount($account->id);

        $this->assertSame('acct_seller', $account->id);
        $this->assertSame('https://connect.stripe.test/onboarding', $link->url);
        $this->assertSame('acct_seller', $retrieved->id);

        $createRequest = $httpClient->requests[0];
        $this->assertSame('post', $createRequest['method']);
        $this->assertStringEndsWith('/v2/core/accounts', $createRequest['url']);
        $this->assertSame('v2', $createRequest['api_mode']);
        $this->assertSame('full', $createRequest['params']['dashboard']);
        $this->assertSame('stripe', $createRequest['params']['defaults']['responsibilities']['fees_collector']);
        $this->assertSame('stripe', $createRequest['params']['defaults']['responsibilities']['losses_collector']);
        $this->assertTrue($createRequest['params']['configuration']['merchant']['capabilities']['card_payments']['requested']);
        $this->assertTrue($createRequest['params']['configuration']['recipient']['capabilities']['stripe_balance']['stripe_transfers']['requested']);
        $this->assertSame('US', $createRequest['params']['identity']['country']);
        $this->assertContains('Idempotency-Key: account-key', $createRequest['headers']);

        $linkRequest = $httpClient->requests[1];
        $this->assertStringEndsWith('/v2/core/account_links', $linkRequest['url']);
        $this->assertSame('account_onboarding', $linkRequest['params']['use_case']['type']);
        $this->assertSame('https://example.test/refresh', $linkRequest['params']['use_case']['account_onboarding']['refresh_url']);
        $this->assertSame('currently_due', $linkRequest['params']['use_case']['account_onboarding']['collection_options']['fields']);
        $this->assertSame('omit', $linkRequest['params']['use_case']['account_onboarding']['collection_options']['future_requirements']);

        $retrieveRequest = $httpClient->requests[2];
        $this->assertSame('get', $retrieveRequest['method']);
        $this->assertStringEndsWith('/v2/core/accounts/acct_seller', $retrieveRequest['url']);
        $this->assertContains('configuration.merchant', $retrieveRequest['params']['include']);
        $this->assertContains('configuration.recipient', $retrieveRequest['params']['include']);
        $this->assertContains('requirements', $retrieveRequest['params']['include']);
    }
}

class RecordingStripeHttpClient implements ClientInterface
{
    public array $requests = [];

    public function request(
        $method,
        $absUrl,
        $headers,
        $params,
        $hasFile,
        $apiMode = 'v1',
        $maxNetworkRetries = null
    ): array {
        $this->requests[] = [
            'method' => $method,
            'url' => $absUrl,
            'headers' => $headers,
            'params' => $params,
            'api_mode' => $apiMode,
        ];

        if (str_ends_with($absUrl, '/v2/core/account_links')) {
            return [json_encode([
                'object' => 'v2.core.account_link',
                'account' => 'acct_seller',
                'url' => 'https://connect.stripe.test/onboarding',
            ], JSON_THROW_ON_ERROR), 200, []];
        }

        return [json_encode([
            'id' => 'acct_seller',
            'object' => 'v2.core.account',
            'configuration' => [],
            'requirements' => ['entries' => []],
        ], JSON_THROW_ON_ERROR), 200, []];
    }
}
