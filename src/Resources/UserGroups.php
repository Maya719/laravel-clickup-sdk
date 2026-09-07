<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * User Group ("Team" in the ClickUp UI) endpoints.
 *
 * @see https://developer.clickup.com/reference/getteams1
 */
class UserGroups extends Resource
{
    /**
     * @param  array<string, mixed>  $query  Supports `group_ids`.
     * @return array<int, mixed>
     */
    public function all(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/group', $query),
            'groups'
        );
    }

    /**
     * @param  array<int, int|string>  $memberIds
     * @return array<string, mixed>
     */
    public function create(string $workspaceId, string $name, array $memberIds = []): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/group', [
            'name' => $name,
            'members' => array_values($memberIds),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data  name, handle, members (['add' => [], 'rem' => []])
     * @return array<string, mixed>
     */
    public function update(string $groupId, array $data): array
    {
        return $this->client->put('group/' . $this->segment($groupId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $groupId): array
    {
        return $this->client->delete('group/' . $this->segment($groupId));
    }
}
