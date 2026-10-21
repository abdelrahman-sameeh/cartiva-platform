<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SellerStripeAccount;
use App\Models\StripeWebhookEvent;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\StripeClient;

class StripeConnectWebhookController extends Controller
{
    private const ACCOUNT_STATUS_EVENTS = [
        'v2.core.account.updated',
        'v2.core.account[configuration.customer].capability_status_updated',
        'v2.core.account[configuration.customer].updated',
        'v2.core.account[configuration.recipient].capability_status_updated',
        'v2.core.account[configuration.recipient].updated',
        'v2.core.account[requirements].updated',
    ];

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly SellerOnboardingService $onboardingService
    ) {}

    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.connect.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new LogicException('STRIPE_CONNECT_WEBHOOK_SECRET is not configured.');
        }

        try {
            $notification = $this->stripe->parseEventNotification(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->noContent(400);
        }

        if (! in_array($notification->type, self::ACCOUNT_STATUS_EVENTS, true)) {
            return response()->noContent();
        }

        $event = StripeWebhookEvent::pending($notification->id, $notification->type);

        if (! $event) {
            return response()->noContent();
        }

        $localAccount = SellerStripeAccount::query()
            ->where('stripe_account_id', $notification->related_object->id)
            ->first();

        if ($localAccount) {
            $this->onboardingService->sync($localAccount);
        }

        $event->markProcessed();

        return response()->noContent();
    }
}
