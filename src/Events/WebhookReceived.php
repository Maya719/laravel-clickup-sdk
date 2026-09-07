<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched for every verified ClickUp webhook delivery.
 *
 * Listen for it in your application:
 *
 * ```php
 * Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
 *     if ($event->event === 'taskStatusUpdated') {
 *         // $event->payload holds the full body ClickUp sent.
 *     }
 * });
 * ```
 */
class WebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string                $event    The ClickUp event name, e.g. "taskCreated".
     * @param  array<string, mixed>  $payload  The full decoded webhook body.
     */
    public function __construct(
        public readonly string $event,
        public readonly array $payload,
    ) {
    }

    /**
     * The id of the task the event concerns, when the event carries one.
     */
    public function taskId(): ?string
    {
        $taskId = $this->payload['task_id'] ?? null;

        return is_string($taskId) ? $taskId : null;
    }

    /**
     * The id of the webhook that delivered this event.
     */
    public function webhookId(): ?string
    {
        $webhookId = $this->payload['webhook_id'] ?? null;

        return is_string($webhookId) ? $webhookId : null;
    }

    /**
     * The `history_items` entries ClickUp attaches to update events.
     *
     * @return array<int, mixed>
     */
    public function historyItems(): array
    {
        $items = $this->payload['history_items'] ?? [];

        return is_array($items) ? array_values($items) : [];
    }
}
