<?php

namespace Tests\Feature\Services;

use App\Exceptions\StripeSettlementNotReadyException;
use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\SellerStripeAccount;
use App\Models\SellerTransfer;
use App\Models\User;
use App\Services\Payments\Gateways\StripeTransferGateway;
use App\Services\Payments\SellerTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Exception\InvalidRequestException;
use Stripe\Transfer;
use Tests\TestCase;

class SellerTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.fees.platform_commission_bps' => 500]);
    }

    public function test_it_does_not_transfer_a_sellers_money_before_their_sub_order_is_completed(): void
    {
        [$order] = $this->paidOrder('75.00');
        $seller = $this->readySeller('acct_wait_for_delivery');
        $subOrder = $order->subOrders()->create([
            'seller_id' => $seller->id,
            'subtotal' => 75,
            'discount' => 0,
            'total_price' => 75,
            'status' => 'pending',
        ]);
        $gateway = Mockery::mock(StripeTransferGateway::class);
        $gateway->shouldNotReceive('sourceTransaction');
        $gateway->shouldNotReceive('createTransfer');
        $this->app->instance(StripeTransferGateway::class, $gateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $subOrder->id,
            'status' => SellerTransfer::STATUS_PENDING,
            'transferred_at' => null,
        ]);
    }

    public function test_it_transfers_each_sub_order_total_once(): void
    {
        [$order, $payment] = $this->paidOrder('150.00');
        $firstSeller = $this->readySeller('acct_first');
        $secondSeller = $this->readySeller('acct_second');
        $firstSubOrder = $order->subOrders()->create([
            'seller_id' => $firstSeller->id,
            'subtotal' => 100,
            'discount' => 0,
            'total_price' => 100,
            'status' => 'completed',
        ]);
        $secondSubOrder = $order->subOrders()->create([
            'seller_id' => $secondSeller->id,
            'subtotal' => 60,
            'discount' => 10,
            'total_price' => 50,
            'status' => 'completed',
        ]);
        $gateway = Mockery::mock(StripeTransferGateway::class);
        $gateway->shouldReceive('sourceTransaction')
            ->once()
            ->with('pi_test_paid_order')
            ->andReturn($this->sourceTransaction(15000, 'egp', null, 450));
        $gateway->shouldReceive('createTransfer')
            ->once()
            ->withArgs(fn (SellerTransfer $transfer, int $orderId, string $chargeId): bool => $transfer->sub_order_id === $firstSubOrder->id
                && $transfer->amount_minor === 10000
                && $transfer->settlement_amount_minor === 10000
                && $transfer->stripe_fee_minor === 300
                && $transfer->platform_fee_minor === 500
                && $transfer->transfer_amount_minor === 9200
                && $transfer->stripe_account_id === 'acct_first'
                && $orderId === $order->id
                && $chargeId === 'ch_test_paid_order')
            ->andReturn(Transfer::constructFrom(['id' => 'tr_first', 'object' => 'transfer']));
        $gateway->shouldReceive('createTransfer')
            ->once()
            ->withArgs(fn (SellerTransfer $transfer): bool => $transfer->sub_order_id === $secondSubOrder->id
                && $transfer->amount_minor === 5000
                && $transfer->settlement_amount_minor === 5000
                && $transfer->stripe_fee_minor === 150
                && $transfer->platform_fee_minor === 250
                && $transfer->transfer_amount_minor === 4600
                && $transfer->stripe_account_id === 'acct_second')
            ->andReturn(Transfer::constructFrom(['id' => 'tr_second', 'object' => 'transfer']));
        $this->app->instance(StripeTransferGateway::class, $gateway);
        $service = app(SellerTransferService::class);

        $service->processPaidOrder($order->id);
        $service->processPaidOrder($order->id);

        $this->assertDatabaseCount('seller_transfers', 2);
        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $firstSubOrder->id,
            'payment_id' => $payment->id,
            'seller_id' => $firstSeller->id,
            'stripe_transfer_id' => 'tr_first',
            'amount_minor' => 10000,
            'currency' => 'egp',
            'settlement_amount_minor' => 10000,
            'settlement_currency' => 'egp',
            'stripe_fee_minor' => 300,
            'platform_fee_minor' => 500,
            'platform_fee_bps' => 500,
            'transfer_amount_minor' => 9200,
            'status' => SellerTransfer::STATUS_SUCCEEDED,
        ]);
        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $secondSubOrder->id,
            'stripe_transfer_id' => 'tr_second',
            'amount_minor' => 5000,
            'status' => SellerTransfer::STATUS_SUCCEEDED,
        ]);
    }

    public function test_a_blocked_transfer_can_continue_after_the_seller_becomes_ready(): void
    {
        [$order] = $this->paidOrder('75.00');
        $seller = User::factory()->create();
        $subOrder = $order->subOrders()->create([
            'seller_id' => $seller->id,
            'subtotal' => 75,
            'discount' => 0,
            'total_price' => 75,
            'status' => 'completed',
        ]);
        $gateway = Mockery::mock(StripeTransferGateway::class);
        $gateway->shouldNotReceive('sourceTransaction');
        $gateway->shouldNotReceive('createTransfer');
        $this->app->instance(StripeTransferGateway::class, $gateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $subOrder->id,
            'stripe_account_id' => null,
            'status' => SellerTransfer::STATUS_BLOCKED,
        ]);

        $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_now_ready',
            'onboarding_status' => SellerStripeAccount::STATUS_READY,
            'transfers_status' => 'active',
        ]);
        $readyGateway = Mockery::mock(StripeTransferGateway::class);
        $readyGateway->shouldReceive('sourceTransaction')->once()->andReturn($this->sourceTransaction(7500));
        $readyGateway->shouldReceive('createTransfer')
            ->once()
            ->andReturn(Transfer::constructFrom(['id' => 'tr_unblocked', 'object' => 'transfer']));
        $this->app->instance(StripeTransferGateway::class, $readyGateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $subOrder->id,
            'stripe_account_id' => 'acct_now_ready',
            'stripe_transfer_id' => 'tr_unblocked',
            'status' => SellerTransfer::STATUS_SUCCEEDED,
        ]);
    }

    public function test_it_keeps_a_transfer_pending_until_stripe_settlement_data_is_ready(): void
    {
        [$order] = $this->paidOrder('75.00');
        $seller = $this->readySeller('acct_waiting_for_settlement');
        $subOrder = $order->subOrders()->create([
            'seller_id' => $seller->id,
            'subtotal' => 75,
            'discount' => 0,
            'total_price' => 75,
            'status' => 'completed',
        ]);
        $gateway = Mockery::mock(StripeTransferGateway::class);
        $gateway->shouldReceive('sourceTransaction')
            ->once()
            ->andThrow(new StripeSettlementNotReadyException);
        $gateway->shouldNotReceive('createTransfer');
        $this->app->instance(StripeTransferGateway::class, $gateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $this->assertDatabaseHas('seller_transfers', [
            'sub_order_id' => $subOrder->id,
            'status' => SellerTransfer::STATUS_PENDING,
            'failure_reason' => null,
            'attempted_at' => null,
        ]);
    }

    public function test_a_failed_transfer_can_be_retried_without_changing_its_idempotency_key(): void
    {
        [$order] = $this->paidOrder('25.00');
        $seller = $this->readySeller('acct_retry');
        $order->subOrders()->create([
            'seller_id' => $seller->id,
            'subtotal' => 25,
            'discount' => 0,
            'total_price' => 25,
            'status' => 'completed',
        ]);
        $failingGateway = Mockery::mock(StripeTransferGateway::class);
        $failingGateway->shouldReceive('sourceTransaction')->once()->andReturn($this->sourceTransaction(2500));
        $failingGateway->shouldReceive('createTransfer')
            ->once()
            ->andThrow(InvalidRequestException::factory('Temporary test failure.'));
        $this->app->instance(StripeTransferGateway::class, $failingGateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $failedTransfer = SellerTransfer::query()->sole();
        $this->assertSame(SellerTransfer::STATUS_FAILED, $failedTransfer->status);
        $idempotencyKey = $failedTransfer->idempotency_key;
        $netTransferAmount = $failedTransfer->transfer_amount_minor;
        config(['services.stripe.fees.platform_commission_bps' => 1000]);
        $successfulGateway = Mockery::mock(StripeTransferGateway::class);
        $successfulGateway->shouldReceive('sourceTransaction')->once()->andReturn($this->sourceTransaction(2500));
        $successfulGateway->shouldReceive('createTransfer')
            ->once()
            ->withArgs(fn (SellerTransfer $transfer): bool => $transfer->idempotency_key === $idempotencyKey
                && $transfer->platform_fee_bps === 500
                && $transfer->transfer_amount_minor === $netTransferAmount)
            ->andReturn(Transfer::constructFrom(['id' => 'tr_after_retry', 'object' => 'transfer']));
        $this->app->instance(StripeTransferGateway::class, $successfulGateway);

        app(SellerTransferService::class)->retryOutstanding($order->id);

        $this->assertDatabaseHas('seller_transfers', [
            'id' => $failedTransfer->id,
            'idempotency_key' => $idempotencyKey,
            'platform_fee_bps' => 500,
            'transfer_amount_minor' => $netTransferAmount,
            'stripe_transfer_id' => 'tr_after_retry',
            'status' => SellerTransfer::STATUS_SUCCEEDED,
        ]);
    }

    public function test_it_allocates_a_different_stripe_settlement_currency_without_losing_order_values(): void
    {
        [$order] = $this->paidOrder('150.00');
        $firstSeller = $this->readySeller('acct_fx_first');
        $secondSeller = $this->readySeller('acct_fx_second');
        $firstSubOrder = $order->subOrders()->create([
            'seller_id' => $firstSeller->id,
            'subtotal' => 100,
            'discount' => 0,
            'total_price' => 100,
            'status' => 'completed',
        ]);
        $secondSubOrder = $order->subOrders()->create([
            'seller_id' => $secondSeller->id,
            'subtotal' => 50,
            'discount' => 0,
            'total_price' => 50,
            'status' => 'completed',
        ]);
        $gateway = Mockery::mock(StripeTransferGateway::class);
        $gateway->shouldReceive('sourceTransaction')
            ->once()
            ->andReturn($this->sourceTransaction(268, 'usd', 15000, 20));
        $gateway->shouldReceive('createTransfer')
            ->once()
            ->withArgs(fn (SellerTransfer $transfer): bool => $transfer->sub_order_id === $firstSubOrder->id
                && $transfer->amount_minor === 10000
                && $transfer->currency === 'egp'
                && $transfer->settlement_amount_minor === 179
                && $transfer->settlement_currency === 'usd'
                && $transfer->stripe_fee_minor === 13
                && $transfer->platform_fee_minor === 9
                && $transfer->transfer_amount_minor === 157)
            ->andReturn(Transfer::constructFrom(['id' => 'tr_fx_first', 'object' => 'transfer']));
        $gateway->shouldReceive('createTransfer')
            ->once()
            ->withArgs(fn (SellerTransfer $transfer): bool => $transfer->sub_order_id === $secondSubOrder->id
                && $transfer->amount_minor === 5000
                && $transfer->currency === 'egp'
                && $transfer->settlement_amount_minor === 89
                && $transfer->settlement_currency === 'usd'
                && $transfer->stripe_fee_minor === 7
                && $transfer->platform_fee_minor === 4
                && $transfer->transfer_amount_minor === 78)
            ->andReturn(Transfer::constructFrom(['id' => 'tr_fx_second', 'object' => 'transfer']));
        $this->app->instance(StripeTransferGateway::class, $gateway);

        app(SellerTransferService::class)->processPaidOrder($order->id);

        $this->assertSame(268, SellerTransfer::query()->sum('settlement_amount_minor'));
    }

    private function paidOrder(string $total): array
    {
        $buyer = User::factory()->create();
        $order = Order::create([
            'user_id' => $buyer->id,
            'subtotal' => $total,
            'discount' => 0,
            'total_price' => $total,
            'phone' => '01012345678',
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'card',
        ]);
        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_paid_order',
            'checkout_session_id' => 'cs_test_paid_order',
            'idempotency_key' => (string) str()->uuid(),
            'amount_minor' => (int) ((float) $total * 100),
            'currency' => 'egp',
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return [$order, $payment];
    }

    private function readySeller(string $stripeAccountId): User
    {
        $seller = User::factory()->create();
        $seller->stripeAccount()->create([
            'stripe_account_id' => $stripeAccountId,
            'onboarding_status' => SellerStripeAccount::STATUS_READY,
            'transfers_status' => 'active',
        ]);

        return $seller;
    }

    private function sourceTransaction(
        int $settlementAmount,
        string $settlementCurrency = 'egp',
        ?int $paymentAmount = null,
        int $stripeFee = 0
    ): array {
        return [
            'charge_id' => 'ch_test_paid_order',
            'payment_amount_minor' => $paymentAmount ?? $settlementAmount,
            'payment_currency' => 'egp',
            'settlement_amount_minor' => $settlementAmount,
            'settlement_currency' => $settlementCurrency,
            'stripe_fee_minor' => $stripeFee,
        ];
    }
}
