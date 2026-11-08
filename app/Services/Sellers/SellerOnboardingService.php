<?php

namespace App\Services\Sellers;

use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use LogicException;
use Stripe\V2\Core\Account;

class SellerOnboardingService
{
    public function __construct(private readonly StripeConnectGateway $stripe) {}

    public function accountFor(User $seller): SellerStripeAccount
    {
        $existingAccount = $seller->stripeAccount()->first();

        if ($existingAccount) {
            return $existingAccount;
        }

        $stripeAccount = $this->stripe->createConnectedAccount(
            $seller,
            "cartiva:seller-connect-account:express-recipient-v1:{$seller->getKey()}"
        );

        return SellerStripeAccount::query()->firstOrCreate(
            ['seller_id' => $seller->getKey()],
            $this->statusAttributes($stripeAccount)
        );
    }

    public function onboardingUrl(User $seller, string $refreshUrl, string $returnUrl): string
    {
        $localAccount = $this->accountFor($seller);
        $accountLink = $this->stripe->createOnboardingLink(
            $localAccount->stripe_account_id,
            $refreshUrl,
            $returnUrl
        );

        return $accountLink->url;
    }

    public function sync(SellerStripeAccount $localAccount): SellerStripeAccount
    {
        $stripeAccount = $this->stripe->retrieveConnectedAccount(
            $localAccount->stripe_account_id
        );

        if ($stripeAccount->id !== $localAccount->stripe_account_id) {
            throw new LogicException('Stripe returned a different connected account.');
        }

        $localAccount->update($this->statusAttributes($stripeAccount));

        return $localAccount->refresh();
    }

    private function statusAttributes(Account $account): array
    {
        $requirements = collect($account->requirements?->entries ?? [])
            ->map(fn ($entry): array => [
                'description' => $entry->description,
                'awaiting_action_from' => $entry->awaiting_action_from,
                'deadline_status' => $entry->minimum_deadline?->status,
            ])
            ->values()
            ->all();
        $transfers = $account->configuration?->recipient?->capabilities?->stripe_balance?->stripe_transfers;

        return [
            'stripe_account_id' => $account->id,
            'onboarding_status' => $this->onboardingStatus($transfers?->status, $requirements),
            'payouts_status' => $account->configuration?->recipient?->capabilities?->stripe_balance?->payouts?->status,
            'transfers_status' => $transfers?->status,
            'requirements_due' => $requirements,
            'disabled_reason' => collect($transfers?->status_details ?? [])->first()?->code,
            'last_synced_at' => now(),
        ];
    }

    private function onboardingStatus(?string $transfersStatus, array $requirements): string
    {
        if ($transfersStatus === 'active') {
            return SellerStripeAccount::STATUS_READY;
        }

        if (collect($requirements)->contains('awaiting_action_from', 'user')) {
            return SellerStripeAccount::STATUS_ACTION_REQUIRED;
        }

        if ($transfersStatus === 'restricted') {
            return SellerStripeAccount::STATUS_RESTRICTED;
        }

        return SellerStripeAccount::STATUS_PENDING;
    }
}
