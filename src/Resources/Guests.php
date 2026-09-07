<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Guest management. These endpoints are Enterprise plan only.
 *
 * @see https://developer.clickup.com/reference/inviteguesttoworkspace
 */
class Guests extends Resource
{
    /**
     * @param  array<string, mixed>  $options  can_edit_tags, can_see_time_spent, can_see_time_estimated, can_create_views
     * @return array<string, mixed>
     */
    public function invite(string $workspaceId, string $email, array $options = []): array
    {
        return $this->client->post(
            'team/' . $this->segment($workspaceId) . '/guest',
            array_merge($options, ['email' => $email])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $workspaceId, string $guestId): array
    {
        return $this->client->get(
            'team/' . $this->segment($workspaceId) . '/guest/' . $this->segment($guestId)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $workspaceId, string $guestId, array $data): array
    {
        return $this->client->put(
            'team/' . $this->segment($workspaceId) . '/guest/' . $this->segment($guestId),
            $data
        );
    }

    /**
     * @return array<mixed>
     */
    public function remove(string $workspaceId, string $guestId): array
    {
        return $this->client->delete(
            'team/' . $this->segment($workspaceId) . '/guest/' . $this->segment($guestId)
        );
    }

    /**
     * Share a task with a guest. `permission_level` is one of read, comment, edit, create.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function addToTask(string $taskId, string $guestId, string $permissionLevel, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/guest/' . $this->segment($guestId),
            ['permission_level' => $permissionLevel],
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function removeFromTask(string $taskId, string $guestId, array $query = []): array
    {
        return $this->client->delete(
            'task/' . $this->segment($taskId) . '/guest/' . $this->segment($guestId),
            $query
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function addToList(string $listId, string $guestId, string $permissionLevel): array
    {
        return $this->client->post(
            'list/' . $this->segment($listId) . '/guest/' . $this->segment($guestId),
            ['permission_level' => $permissionLevel]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function removeFromList(string $listId, string $guestId): array
    {
        return $this->client->delete(
            'list/' . $this->segment($listId) . '/guest/' . $this->segment($guestId)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function addToFolder(string $folderId, string $guestId, string $permissionLevel): array
    {
        return $this->client->post(
            'folder/' . $this->segment($folderId) . '/guest/' . $this->segment($guestId),
            ['permission_level' => $permissionLevel]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function removeFromFolder(string $folderId, string $guestId): array
    {
        return $this->client->delete(
            'folder/' . $this->segment($folderId) . '/guest/' . $this->segment($guestId)
        );
    }
}
