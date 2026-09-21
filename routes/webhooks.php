<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MCKLtech\MightyNetworks\Webhooks\Middleware\VerifyWebhookSecret;
use MCKLtech\MightyNetworks\Webhooks\WebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Loaded by the service provider only when `mighty-networks.webhooks.enabled`
| is true. Mighty Networks delivers webhooks without an HMAC signature, so the
| only guard is the package's own constant-time Bearer-secret verifier.
|
*/

$configuredPath = config('mighty-networks.webhooks.path', 'webhooks/mighty-networks');

$path = is_string($configuredPath) && trim($configuredPath) !== ''
    ? ltrim($configuredPath, '/')
    : 'webhooks/mighty-networks';

Route::post($path, WebhookController::class)
    ->middleware(VerifyWebhookSecret::class)
    ->name('mighty-networks.webhooks');
