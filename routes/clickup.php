<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Maya719\ClickUp\Http\Controllers\WebhookController;
use Maya719\ClickUp\Http\Middleware\VerifyClickUpWebhookSignature;

/*
|--------------------------------------------------------------------------
| ClickUp Webhook Route
|--------------------------------------------------------------------------
|
| Loaded by the service provider when `clickup.webhooks.route.enabled` is true.
| The prefix, name and middleware all come from config/clickup.php.
|
*/

Route::post(config('clickup.webhooks.route.path', 'clickup/webhook'), WebhookController::class)
    ->middleware(array_merge(
        (array) config('clickup.webhooks.route.middleware', []),
        [VerifyClickUpWebhookSignature::class]
    ))
    ->name(config('clickup.webhooks.route.name', 'clickup.webhook'));
