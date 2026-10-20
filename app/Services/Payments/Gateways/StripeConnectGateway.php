<?php

namespace App\Services\Payments\Gateways;

use App\Models\User;
use Stripe\StripeClient;
use Stripe\V2\Core\Account;
use Stripe\V2\Core\AccountLink;

class StripeConnectGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createConnectedAccount(User $seller, string $idempotencyKey): Account
    {
        $parameters = [
            'contact_email' => $seller->email,
            'display_name' => trim("{$seller->first_name} {$seller->last_name}"),
            'dashboard' => 'full',
            'defaults' => [
                'responsibilities' => [
                    'fees_collector' => 'stripe',
                    'losses_collector' => 'stripe',
                ],
            ],
            'configuration' => [
                'merchant' => [
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                    ],
                ],
                'recipient' => [
                    'capabilities' => [
                        'stripe_balance' => [
                            'stripe_transfers' => ['requested' => true],
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'seller_id' => (string) $seller->getKey(),
            ],
            'include' => $this->accountIncludes(),
        ];

        $country = config('services.stripe.connect.country');

        if (is_string($country) && $country !== '') {
            $parameters['identity'] = ['country' => strtoupper($country)];
        }

        return $this->stripe->v2->core->accounts->create($parameters, [
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function createOnboardingLink(
        string $stripeAccountId,
        string $refreshUrl,
        string $returnUrl
    ): AccountLink {
        return $this->stripe->v2->core->accountLinks->create([
            'account' => $stripeAccountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                ],
            ],
        ]);
    }

    public function retrieveConnectedAccount(string $stripeAccountId): Account
    {
        return $this->stripe->v2->core->accounts->retrieve($stripeAccountId, [
            'include' => $this->accountIncludes(),
        ]);
    }

    private function accountIncludes(): array
    {
        return [
            'configuration.merchant',
            'configuration.recipient',
            'requirements',
        ];
    }
}
