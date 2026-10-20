<?php

namespace App\Services\Sellers;

use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Payments\Gateways\StripeConnectGateway;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($seller): SellerStripeAccount {
            $lockedSeller = User::query()->lockForUpdate()->findOrFail($seller->getKey());
            $existingAccount = $lockedSeller->stripeAccount()->first();

            if ($existingAccount) {
                return $existingAccount;
            }

            $stripeAccount = $this->stripe->createConnectedAccount(
                $lockedSeller,
                "printore:seller-connect-account:{$lockedSeller->getKey()}"
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
            'card_payments_status' => $account->configuration?->merchant?->capabilities?->card_payments?->status,
            'payouts_status' => $account->configuration?->merchant?->capabilities?->stripe_balance?->payouts?->status,
            'transfers_status' => $account->configuration?->recipient?->capabilities?->stripe_balance?->stripe_transfers?->status,
            'requirements' => collect($account->requirements?->entries ?? [])
                ->map(fn ($entry): array => [
                    'description' => $entry->description,
                    'awaiting_action_from' => $entry->awaiting_action_from,
                    'deadline_status' => $entry->minimum_deadline?->status,
                ])
                ->values()
                ->all(),
            'last_synced_at' => now(),
        ];
    }
}
