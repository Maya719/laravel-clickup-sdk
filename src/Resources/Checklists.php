<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Task checklist endpoints.
 *
 * @see https://developer.clickup.com/reference/createchecklist
 */
class Checklists extends Resource
{
    /**
     * @param  array<string, mixed>  $data   Requires `name`.
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function create(string $taskId, array $data, array $query = []): array
    {
        return $this->client->post('task/' . $this->segment($taskId) . '/checklist', $data, $query);
    }

    /**
     * Rename a checklist or move it with `position`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $checklistId, array $data): array
    {
        return $this->client->put('checklist/' . $this->segment($checklistId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $checklistId): array
    {
        return $this->client->delete('checklist/' . $this->segment($checklistId));
    }

    /**
     * @param  array<string, mixed>  $data  Requires `name`; accepts `assignee`.
     * @return array<string, mixed>
     */
    public function createItem(string $checklistId, array $data): array
    {
        return $this->client->post('checklist/' . $this->segment($checklistId) . '/checklist_item', $data);
    }

    /**
     * @param  array<string, mixed>  $data  name, assignee, resolved, parent
     * @return array<string, mixed>
     */
    public function updateItem(string $checklistId, string $itemId, array $data): array
    {
        return $this->client->put(
            'checklist/' . $this->segment($checklistId) . '/checklist_item/' . $this->segment($itemId),
            $data
        );
    }

    /**
     * @return array<mixed>
     */
    public function deleteItem(string $checklistId, string $itemId): array
    {
        return $this->client->delete(
            'checklist/' . $this->segment($checklistId) . '/checklist_item/' . $this->segment($itemId)
        );
    }
}
