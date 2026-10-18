<?php

namespace App\Services\Sellers;

use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use Illuminate\Support\Facades\DB;
use LogicException;
use Stripe\Account;

class SellerOnboardingService
{
    public function __construct(private readonly StripeConnectGateway $stripe) {}

    public function accountFor(User $seller): SellerStripeAccount
    {
        $existingAccount = $seller->stripeAccount()->first();

        if ($existingAccount) {
            return $existingAccount;
        }

        return DB::transaction(function () use ($seller): SellerStripeAccount {
            $lockedSeller = User::query()->lockForUpdate()->findOrFail($seller->getKey());
            $existingAccount = $lockedSeller->stripeAccount()->first();

            if ($existingAccount) {
                return $existingAccount;
            }

            $stripeAccount = $this->stripe->createConnectedAccount(
                $lockedSeller,
                "printora:seller-connect-account:{$lockedSeller->getKey()}"
            );

            return $lockedSeller->stripeAccount()->create(
                $this->statusAttributes($stripeAccount)
            );
        });
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
        return [
            'stripe_account_id' => $account->id,
            'details_submitted' => (bool) $account->details_submitted,
            'charges_enabled' => (bool) $account->charges_enabled,
            'payouts_enabled' => (bool) $account->payouts_enabled,
            'disabled_reason' => $account->requirements?->disabled_reason,
            'requirements_currently_due' => array_values(
                $account->requirements?->currently_due ?? []
            ),
            'last_synced_at' => now(),
        ];
    }
}
