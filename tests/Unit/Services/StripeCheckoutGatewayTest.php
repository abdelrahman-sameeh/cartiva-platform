<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Gateways\StripeCheckoutGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeCheckoutGatewayTest extends TestCase
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

    public function test_it_creates_a_platform_checkout_session_for_the_server_total(): void
    {
        $httpClient = new CheckoutRecordingHttpClient;
        ApiRequestor::setHttpClient($httpClient);
        $gateway = new StripeCheckoutGateway(new StripeClient('sk_test_example'));
        $payment = (new Payment)->forceFill([
            'id' => 17,
            'order_id' => 42,
            'amount_minor' => 18000,
            'currency' => 'egp',
            'idempotency_key' => 'payment-key',
        ]);
        $customer = (new User)->forceFill(['email' => 'buyer@example.test']);

        $session = $gateway->createSession(
            $payment,
            $customer,
            'https://example.test/success',
            'https://example.test/cancel'
        );

        $this->assertSame('cs_test_order', $session->id);
        $request = $httpClient->requests[0];
        $this->assertSame('post', $request['method']);
        $this->assertStringEndsWith('/v1/checkout/sessions', $request['url']);
        $this->assertSame('payment', $request['params']['mode']);
        $this->assertSame(18000, $request['params']['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('egp', $request['params']['line_items'][0]['price_data']['currency']);
        $this->assertSame('42', $request['params']['metadata']['order_id']);
        $this->assertSame('17', $request['params']['metadata']['payment_id']);
        $this->assertSame('ORDER_42', $request['params']['payment_intent_data']['transfer_group']);
        $this->assertArrayNotHasKey('payment_method_types', $request['params']);
        $this->assertContains('Idempotency-Key: payment-key', $request['headers']);
        $this->assertArrayNotHasKey('stripe_account', $request['params']);
    }
}

class CheckoutRecordingHttpClient implements ClientInterface
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
        ];

        return [json_encode([
            'id' => 'cs_test_order',
            'object' => 'checkout.session',
            'mode' => 'payment',
            'status' => 'open',
            'payment_status' => 'unpaid',
            'url' => 'https://checkout.stripe.test/order',
        ], JSON_THROW_ON_ERROR), 200, []];
    }
}
