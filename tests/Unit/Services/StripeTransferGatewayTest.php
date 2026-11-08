<?php

namespace Tests\Unit\Services;

use App\Exceptions\StripeSettlementNotReadyException;
use App\Models\SellerTransfer;
use App\Services\Payments\Gateways\StripeTransferGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeTransferGatewayTest extends TestCase
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

    public function test_it_uses_the_payment_charge_and_an_idempotent_transfer(): void
    {
        $httpClient = new TransferRecordingHttpClient;
        ApiRequestor::setHttpClient($httpClient);
        $gateway = new StripeTransferGateway(new StripeClient('sk_test_example'));
        $sellerTransfer = (new SellerTransfer)->forceFill([
            'id' => 8,
            'payment_id' => 17,
            'sub_order_id' => 31,
            'seller_id' => 9,
            'stripe_account_id' => 'acct_seller',
            'idempotency_key' => 'seller-transfer-key',
            'amount_minor' => 12500,
            'currency' => 'egp',
            'settlement_amount_minor' => 223,
            'settlement_currency' => 'usd',
            'stripe_fee_minor' => 18,
            'platform_fee_minor' => 11,
            'platform_fee_bps' => 500,
            'transfer_amount_minor' => 194,
        ]);

        $sourceTransaction = $gateway->sourceTransaction('pi_test_order');
        $transfer = $gateway->createTransfer($sellerTransfer, 42, $sourceTransaction['charge_id']);

        $this->assertSame([
            'charge_id' => 'ch_test_order',
            'payment_amount_minor' => 12500,
            'payment_currency' => 'egp',
            'settlement_amount_minor' => 223,
            'settlement_currency' => 'usd',
            'stripe_fee_minor' => 18,
        ], $sourceTransaction);
        $this->assertSame('tr_test_seller', $transfer->id);
        $request = $httpClient->requests[1];
        $this->assertSame('post', $request['method']);
        $this->assertStringEndsWith('/v1/transfers', $request['url']);
        $this->assertSame(194, $request['params']['amount']);
        $this->assertSame('usd', $request['params']['currency']);
        $this->assertSame('acct_seller', $request['params']['destination']);
        $this->assertSame('ch_test_order', $request['params']['source_transaction']);
        $this->assertSame('ORDER_42', $request['params']['transfer_group']);
        $this->assertSame('31', $request['params']['metadata']['sub_order_id']);
        $this->assertContains('Idempotency-Key: seller-transfer-key', $request['headers']);
    }

    public function test_it_reports_when_stripe_settlement_data_is_not_ready_yet(): void
    {
        $httpClient = new TransferRecordingHttpClient;
        $httpClient->balanceTransactionReady = false;
        ApiRequestor::setHttpClient($httpClient);
        $gateway = new StripeTransferGateway(new StripeClient('sk_test_example'));

        $this->expectException(StripeSettlementNotReadyException::class);

        $gateway->sourceTransaction('pi_test_order');
    }
}

class TransferRecordingHttpClient implements ClientInterface
{
    public array $requests = [];

    public bool $balanceTransactionReady = true;

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
        $response = str_contains($absUrl, '/payment_intents/')
            ? [
                'id' => 'pi_test_order',
                'object' => 'payment_intent',
                'amount' => 12500,
                'currency' => 'egp',
                'latest_charge' => [
                    'id' => 'ch_test_order',
                    'object' => 'charge',
                    'balance_transaction' => $this->balanceTransactionReady ? [
                        'id' => 'txn_test_order',
                        'object' => 'balance_transaction',
                        'amount' => 223,
                        'currency' => 'usd',
                        'fee' => 18,
                    ] : null,
                ],
            ]
            : ['id' => 'tr_test_seller', 'object' => 'transfer'];

        return [json_encode($response, JSON_THROW_ON_ERROR), 200, []];
    }
}
