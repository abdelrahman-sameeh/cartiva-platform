<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LogicException;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            $secret = config('services.stripe.secret');

            if (! is_string($secret) || $secret === '') {
                throw new LogicException('STRIPE_SECRET is not configured.');
            }

            return new StripeClient($secret);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
