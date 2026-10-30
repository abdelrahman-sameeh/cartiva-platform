<?php

namespace App\Services\Payments;

use LogicException;

class SellerProfitCalculator
{
    public function calculate(
        string $unitPrice,
        ?string $unitCost,
        int $quantity,
        string $usdEgpRate,
        bool $internationalCard = false
    ): array {
        $grossAmount = $this->minorUnits($unitPrice) * $quantity;
        $costAmount = $unitCost !== null
            ? $this->minorUnits($unitCost) * $quantity
            : null;
        $platformFeeBps = $this->configBasisPoints('platform_commission_bps');
        $stripeFeeBps = $this->configBasisPoints('estimated_processing_bps')
            + $this->configBasisPoints('estimated_currency_conversion_bps')
            + ($internationalCard ? $this->configBasisPoints('estimated_international_bps') : 0);
        $fixedStripeFee = (int) round(
            (int) config('services.stripe.fees.estimated_fixed_usd_minor', 30) * (float) $usdEgpRate
        );
        $stripeFee = $this->percentageOf($grossAmount, $stripeFeeBps) + $fixedStripeFee;
        $platformFee = $this->percentageOf($grossAmount, $platformFeeBps);
        $netPayout = max(0, $grossAmount - $stripeFee - $platformFee);
        $profit = $costAmount !== null ? $netPayout - $costAmount : null;

        return [
            'gross_amount_minor' => $grossAmount,
            'cost_amount_minor' => $costAmount,
            'stripe_fee_minor' => $stripeFee,
            'platform_fee_minor' => $platformFee,
            'total_fees_minor' => $stripeFee + $platformFee,
            'net_payout_minor' => $netPayout,
            'profit_minor' => $profit,
            'profit_margin' => $profit !== null && $grossAmount > 0
                ? round(($profit / $grossAmount) * 100, 1)
                : null,
            'platform_fee_percent' => $platformFeeBps / 100,
            'stripe_fee_percent' => $stripeFeeBps / 100,
            'fixed_stripe_fee_minor' => $fixedStripeFee,
        ];
    }

    private function minorUnits(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new LogicException('Money values must have at most two decimal places.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    private function percentageOf(int $amount, int $basisPoints): int
    {
        return intdiv(($amount * $basisPoints) + 5000, 10000);
    }

    private function configBasisPoints(string $key): int
    {
        $basisPoints = (int) config("services.stripe.fees.{$key}");

        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new LogicException("{$key} must be between 0 and 100 percent.");
        }

        return $basisPoints;
    }
}
