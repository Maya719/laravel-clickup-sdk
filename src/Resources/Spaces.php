<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Space endpoints.
 *
 * @see https://developer.clickup.com/reference/getspaces
 */
class Spaces extends Resource
{
    /**
     * @param  array<string, mixed>  $query  Supports `archived`.
     * @return array<int, mixed>
     */
    public function all(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/space', $query),
            'spaces'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $spaceId): array
    {
        return $this->client->get('space/' . $this->segment($spaceId));
    }

    /**
     * @param  array<string, mixed>  $data  name, multiple_assignees, features
     * @return array<string, mixed>
     */
    public function create(string $workspaceId, array $data): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/space', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $spaceId, array $data): array
    {
        return $this->client->put('space/' . $this->segment($spaceId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $spaceId): array
    {
        return $this->client->delete('space/' . $this->segment($spaceId));
    }
}
