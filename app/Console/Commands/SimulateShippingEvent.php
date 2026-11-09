<?php

namespace App\Console\Commands;

use App\Services\Shipping\DemoShippingService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class SimulateShippingEvent extends Command
{
    protected $signature = 'shipping:demo
        {tracking : Demo shipment tracking number}
        {event : picked-up, in-transit, delivered, remitted, returned, or seller-paid}';

    protected $description = 'Simulate an event from the demo shipping company';

    public function handle(DemoShippingService $shipping): int
    {
        try {
            $shipment = $shipping->handleEvent(
                (string) $this->argument('tracking'),
                (string) $this->argument('event')
            );
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->first());

            return self::FAILURE;
        }

        $this->info("Shipment {$shipment->tracking_number}: {$shipment->status} / COD {$shipment->cod_status}");

        return self::SUCCESS;
    }
}
