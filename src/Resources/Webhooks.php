<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Webhook management endpoints.
 *
 * @see https://developer.clickup.com/reference/createwebhook
 */
class Webhooks extends Resource
{
    /**
     * Every webhook created by this token in the Workspace.
     *
     * @return array<int, mixed>
     */
    public function all(string $workspaceId): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/webhook'),
            'webhooks'
        );
    }

    /**
     * Register a webhook.
     *
     * Scope it by passing one of space_id, folder_id, list_id or task_id; leave
     * them out to receive events for the whole Workspace. Use `['*']` for events
     * to subscribe to everything.
     *
     * @param  array<int, string>    $events
     * @param  array<string, mixed>  $options  space_id, folder_id, list_id, task_id
     * @return array<string, mixed>
     */
    public function create(string $workspaceId, string $endpoint, array $events = ['*'], array $options = []): array
    {
        return $this->client->post(
            'team/' . $this->segment($workspaceId) . '/webhook',
            array_merge($options, [
                'endpoint' => $endpoint,
                'events' => array_values($events),
            ])
        );
    }

    /**
     * Update a webhook. `status` may be set to "active" to re-enable a webhook
     * ClickUp disabled after repeated delivery failures.
     *
     * @param  array<string, mixed>  $data  endpoint, events, status, space_id, folder_id, list_id, task_id
     * @return array<string, mixed>
     */
    public function update(string $webhookId, array $data): array
    {
        return $this->client->put('webhook/' . $this->segment($webhookId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $webhookId): array
    {
        return $this->client->delete('webhook/' . $this->segment($webhookId));
    }

    /**
     * Every event name ClickUp can deliver.
     *
     * @return array<int, string>
     */
    public static function events(): array
    {
        return [
            'taskCreated',
            'taskUpdated',
            'taskDeleted',
            'taskPriorityUpdated',
            'taskStatusUpdated',
            'taskAssigneeUpdated',
            'taskDueDateUpdated',
            'taskTagUpdated',
            'taskMoved',
            'taskCommentPosted',
            'taskCommentUpdated',
            'taskTimeEstimateUpdated',
            'taskTimeTrackedUpdated',
            'listCreated',
            'listUpdated',
            'listDeleted',
            'folderCreated',
            'folderUpdated',
            'folderDeleted',
            'spaceCreated',
            'spaceUpdated',
            'spaceDeleted',
            'goalCreated',
            'goalUpdated',
            'goalDeleted',
            'keyResultCreated',
            'keyResultUpdated',
            'keyResultDeleted',
            'automationCreated',
        ];
    }
}
