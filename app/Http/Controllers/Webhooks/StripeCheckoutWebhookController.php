<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\OrderCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

class StripeCheckoutWebhookController extends Controller
{
    private const CHECKOUT_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    public function __construct(private readonly OrderCheckoutService $checkoutService) {}

    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.checkout.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new LogicException('STRIPE_CHECKOUT_WEBHOOK_SECRET is not configured.');
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->noContent(400);
        }

        if (! in_array($event->type, self::CHECKOUT_EVENTS, true)) {
            return response()->noContent();
        }

        $session = $event->data->object;

        if ($session instanceof Session) {
            $this->checkoutService->handleWebhook($event->id, $event->type, $session);
        }

        return response()->noContent();
    }
}
