<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Custom Field endpoints.
 *
 * @see https://developer.clickup.com/reference/getaccessiblecustomfields
 */
class CustomFields extends Resource
{
    /**
     * Fields available on a List.
     *
     * @return array<int, mixed>
     */
    public function forList(string $listId): array
    {
        return $this->unwrap(
            $this->client->get('list/' . $this->segment($listId) . '/field'),
            'fields'
        );
    }

    /**
     * Fields available on a Folder.
     *
     * @return array<int, mixed>
     */
    public function forFolder(string $folderId): array
    {
        return $this->unwrap(
            $this->client->get('folder/' . $this->segment($folderId) . '/field'),
            'fields'
        );
    }

    /**
     * Fields available on a Space.
     *
     * @return array<int, mixed>
     */
    public function forSpace(string $spaceId): array
    {
        return $this->unwrap(
            $this->client->get('space/' . $this->segment($spaceId) . '/field'),
            'fields'
        );
    }

    /**
     * Fields available across a Workspace.
     *
     * @return array<int, mixed>
     */
    public function forWorkspace(string $workspaceId): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/field'),
            'fields'
        );
    }

    /**
     * Set a Custom Field value on a task.
     *
     * The shape of $value depends on the field type: a string for text, a timestamp
     * in milliseconds for date, an option id for drop downs, or
     * `['add' => [...], 'rem' => [...]]` for labels and relationships.
     *
     * @param  array<string, mixed>  $query  Supports `custom_task_ids` + `team_id`.
     * @return array<mixed>
     */
    public function set(string $taskId, string $fieldId, mixed $value, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/field/' . $this->segment($fieldId),
            ['value' => $value],
            $query
        );
    }

    /**
     * Set a Custom Field value alongside extra options, e.g. `value_options`.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function setRaw(string $taskId, string $fieldId, array $payload, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/field/' . $this->segment($fieldId),
            $payload,
            $query
        );
    }

    /**
     * Clear a Custom Field value on a task.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function remove(string $taskId, string $fieldId, array $query = []): array
    {
        return $this->client->delete(
            'task/' . $this->segment($taskId) . '/field/' . $this->segment($fieldId),
            $query
        );
    }
}
