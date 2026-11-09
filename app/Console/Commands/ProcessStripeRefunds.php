<?php

namespace App\Console\Commands;

use App\Services\Payments\RefundService;
use Illuminate\Console\Command;

class ProcessStripeRefunds extends Command
{
    protected $signature = 'stripe:process-refunds';

    protected $description = 'Process pending partial Stripe refunds for cancelled seller sub-orders';

    public function handle(RefundService $refunds): int
    {
        $this->info("Processed {$refunds->processOutstanding()} refund(s).");

        return self::SUCCESS;
    }
}
