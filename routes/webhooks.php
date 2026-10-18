<?php

use App\Http\Controllers\Webhooks\StripeConnectWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe/connect', StripeConnectWebhookController::class)
    ->name('webhooks.stripe.connect');
