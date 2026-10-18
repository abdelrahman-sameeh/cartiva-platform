<?php

namespace App\Services\Payments\Gateways;

use App\Models\User;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\StripeClient;

class StripeConnectGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createConnectedAccount(User $seller, string $idempotencyKey): Account
    {
        $parameters = [
            'type' => 'express',
            'email' => $seller->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'seller_id' => (string) $seller->getKey(),
            ],
        ];

        $country = config('services.stripe.connect.country');

        if (is_string($country) && $country !== '') {
            $parameters['country'] = strtoupper($country);
        }

        return $this->stripe->accounts->create($parameters, [
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function createOnboardingLink(
        string $stripeAccountId,
        string $refreshUrl,
        string $returnUrl
    ): AccountLink {
        return $this->stripe->accountLinks->create([
            'account' => $stripeAccountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);
    }

    public function retrieveConnectedAccount(string $stripeAccountId): Account
    {
        return $this->stripe->accounts->retrieve($stripeAccountId, []);
    }
}
