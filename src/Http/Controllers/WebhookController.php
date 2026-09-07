<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Http\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maya719\ClickUp\Events\WebhookReceived;

/**
 * Receives ClickUp webhook deliveries and re-broadcasts them as Laravel events.
 *
 * The route is registered by the service provider and is protected by
 * {@see \Maya719\ClickUp\Http\Middleware\VerifyClickUpWebhookSignature}.
 */
class WebhookController
{
    public function __invoke(Request $request, Dispatcher $events): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $event = $payload['event'] ?? null;

        if (! is_string($event) || $event === '') {
            return new JsonResponse(['message' => 'Missing webhook event name.'], 422);
        }

        $events->dispatch(new WebhookReceived($event, $payload));

        // ClickUp retries deliveries that do not answer with a 2xx.
        return new JsonResponse(['message' => 'ok']);
    }
}
