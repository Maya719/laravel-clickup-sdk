<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Goal and Key Result (target) endpoints.
 *
 * @see https://developer.clickup.com/reference/getgoals
 */
class Goals extends Resource
{
    /**
     * @param  array<string, mixed>  $query  Supports `include_completed`.
     * @return array<int, mixed>
     */
    public function all(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/goal', $query),
            'goals'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $goalId): array
    {
        return $this->client->get('goal/' . $this->segment($goalId));
    }

    /**
     * @param  array<string, mixed>  $data  name, due_date, description, multiple_owners, owners, color
     * @return array<string, mixed>
     */
    public function create(string $workspaceId, array $data): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/goal', $data);
    }

    /**
     * @param  array<string, mixed>  $data  name, due_date, description, rem_owners, add_owners, color
     * @return array<string, mixed>
     */
    public function update(string $goalId, array $data): array
    {
        return $this->client->put('goal/' . $this->segment($goalId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $goalId): array
    {
        return $this->client->delete('goal/' . $this->segment($goalId));
    }

    /**
     * @param  array<string, mixed>  $data  name, owners, type, steps_start, steps_end, unit, task_ids, list_ids
     * @return array<string, mixed>
     */
    public function createKeyResult(string $goalId, array $data): array
    {
        return $this->client->post('goal/' . $this->segment($goalId) . '/key_result', $data);
    }

    /**
     * @param  array<string, mixed>  $data  steps_current, note
     * @return array<string, mixed>
     */
    public function updateKeyResult(string $keyResultId, array $data): array
    {
        return $this->client->put('key_result/' . $this->segment($keyResultId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function deleteKeyResult(string $keyResultId): array
    {
        return $this->client->delete('key_result/' . $this->segment($keyResultId));
    }
}
