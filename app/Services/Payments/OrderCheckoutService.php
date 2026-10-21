<?php

namespace App\Services\Payments;

use App\Models\Order\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use App\Services\Payments\Gateways\StripeCheckoutGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Stripe\Checkout\Session;
use Stripe\Exception\IdempotencyException;
use Stripe\Exception\InvalidRequestException;

class OrderCheckoutService
{
    private const ACTIVE_STATUSES = [
        Payment::STATUS_CREATING,
        Payment::STATUS_PENDING,
    ];

    public function __construct(private readonly StripeCheckoutGateway $stripe) {}

    public function checkoutUrl(
        User $customer,
        Order $order,
        string $successUrl,
        string $cancelUrl
    ): string {
        $payment = $this->pendingPayment($customer, $order);

        if ($payment->checkout_session_id) {
            $session = $this->stripe->retrieveSession($payment->checkout_session_id);

            if ($session->payment_status === 'paid') {
                DB::transaction(function () use ($payment, $session): void {
                    $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                    $this->markPaid($lockedPayment, $session);
                });

                throw ValidationException::withMessages([
                    'payment' => 'تم دفع هذا الطلب بالفعل.',
                ]);
            }

            if ($session->status === 'open' && is_string($session->url)) {
                return $session->url;
            }

            $payment->update(['status' => Payment::STATUS_EXPIRED]);

            return $this->checkoutUrl($customer, $order->refresh(), $successUrl, $cancelUrl);
        }

        try {
            $session = $this->stripe->createSession(
                $payment,
                $customer,
                $successUrl,
                $cancelUrl
            );
        } catch (IdempotencyException|InvalidRequestException $exception) {
            $payment->update(['status' => Payment::STATUS_FAILED]);

            throw $exception;
        }

        if (! is_string($session->url) || $session->url === '') {
            throw new LogicException('Stripe Checkout did not return a redirect URL.');
        }

        $payment->update([
            'checkout_session_id' => $session->id,
            'provider_payment_id' => $this->paymentIntentId($session),
            'status' => Payment::STATUS_PENDING,
        ]);

        return $session->url;
    }

    public function handleWebhook(string $eventId, string $type, Session $session): void
    {
        DB::transaction(function () use ($eventId, $type, $session): void {
            $event = StripeWebhookEvent::pending($eventId, $type);

            if (! $event) {
                return;
            }

            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where('checkout_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $event->markProcessed();

                return;
            }

            if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                if ($session->payment_status === 'paid') {
                    $this->markPaid($payment, $session);
                }
            } elseif ($type === 'checkout.session.async_payment_failed') {
                $this->markFailed($payment);
            } elseif ($type === 'checkout.session.expired') {
                $this->markExpired($payment);
            }

            $event->markProcessed();
        });
    }

    private function pendingPayment(User $customer, Order $order): Payment
    {
        return DB::transaction(function () use ($customer, $order): Payment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ((int) $lockedOrder->user_id !== (int) $customer->id) {
                abort(403, 'هذا الطلب لا يخص حسابك.');
            }

            if ($lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'payment' => 'تم دفع هذا الطلب بالفعل.',
                ]);
            }

            if (! in_array($lockedOrder->payment_method, ['card', 'wallet'], true)) {
                throw ValidationException::withMessages([
                    'payment' => 'طريقة الدفع المختارة لا تستخدم Stripe Checkout.',
                ]);
            }

            $payment = $lockedOrder->payments()
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->latest('id')
                ->first();

            if ($payment) {
                return $payment;
            }

            $currency = strtolower((string) config('services.stripe.currency', 'egp'));

            if (! preg_match('/^[a-z]{3}$/', $currency)) {
                throw new LogicException('Stripe currency must be a three-letter ISO code.');
            }

            $lockedOrder->update(['payment_status' => 'pending']);

            return $lockedOrder->payments()->create([
                'provider' => 'stripe',
                'idempotency_key' => (string) Str::uuid(),
                'amount_minor' => $this->minorUnits($lockedOrder->total_price),
                'currency' => $currency,
                'status' => Payment::STATUS_CREATING,
            ]);
        });
    }

    private function markPaid(Payment $payment, Session $session): void
    {
        if ($payment->status === Payment::STATUS_PAID) {
            return;
        }

        if (! $this->matchesPayment($payment, $session)) {
            Log::warning('Ignored mismatched Stripe Checkout payment event.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $session->id,
            ]);

            return;
        }

        $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

        $payment->update([
            'provider_payment_id' => $this->paymentIntentId($session),
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        if ($order->payment_status !== 'refunded') {
            $order->update(['payment_status' => 'paid']);
        }
    }

    private function markFailed(Payment $payment): void
    {
        if ($payment->status === Payment::STATUS_PAID) {
            return;
        }

        $payment->update(['status' => Payment::STATUS_FAILED]);

        Order::query()
            ->whereKey($payment->order_id)
            ->where('payment_status', '!=', 'paid')
            ->update(['payment_status' => 'failed']);
    }

    private function markExpired(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_PAID) {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
        }
    }

    private function matchesPayment(Payment $payment, Session $session): bool
    {
        return $session->mode === 'payment'
            && (string) $session->client_reference_id === (string) $payment->order_id
            && (string) ($session->metadata->order_id ?? '') === (string) $payment->order_id
            && (string) ($session->metadata->payment_id ?? '') === (string) $payment->id
            && (int) $session->amount_total === $payment->amount_minor
            && strtolower((string) $session->currency) === $payment->currency
            && $this->paymentIntentId($session) !== null;
    }

    private function paymentIntentId(Session $session): ?string
    {
        if (! isset($session->payment_intent)) {
            return null;
        }

        $paymentIntent = $session->payment_intent;

        if (is_string($paymentIntent)) {
            return $paymentIntent;
        }

        return $paymentIntent?->id;
    }

    private function minorUnits(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new LogicException('Order total must have at most two decimal places.');
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');
        $minorUnits = ($whole * 100) + $fraction;

        if ($minorUnits <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'إجمالي الطلب غير صالح للدفع.',
            ]);
        }

        return $minorUnits;
    }
}
