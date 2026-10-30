<?php

namespace App\Console\Commands;

use App\Services\Payments\SellerTransferService;
use Illuminate\Console\Command;

class RetrySellerTransfers extends Command
{
    protected $signature = 'stripe:retry-seller-transfers {--order= : Retry one order ID only}';

    protected $description = 'Retry pending, failed, blocked, or interrupted Stripe seller transfers';

    public function handle(SellerTransferService $sellerTransfers): int
    {
        $orderId = $this->option('order');

        if ($orderId !== null && filter_var($orderId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->error('The --order option must be a positive integer.');

            return self::FAILURE;
        }

        if ($orderId !== null) {
            $sellerTransfers->processPaidOrder((int) $orderId);
            $processedOrders = 1;
        } else {
            $processedOrders = $sellerTransfers->retryOutstanding();
        }

        $this->info("Processed {$processedOrders} order(s) with outstanding seller transfers.");

        return self::SUCCESS;
    }
}
