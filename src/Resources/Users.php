<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Workspace user management. These endpoints are Enterprise plan only.
 *
 * @see https://developer.clickup.com/reference/inviteusertoworkspace
 */
class Users extends Resource
{
    /**
     * Invite someone to the Workspace by email.
     *
     * @param  array<string, mixed>  $options  admin, custom_role_id
     * @return array<string, mixed>
     */
    public function invite(string $workspaceId, string $email, array $options = []): array
    {
        return $this->client->post(
            'team/' . $this->segment($workspaceId) . '/user',
            array_merge($options, ['email' => $email])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $workspaceId, string $userId): array
    {
        return $this->client->get(
            'team/' . $this->segment($workspaceId) . '/user/' . $this->segment($userId)
        );
    }

    /**
     * @param  array<string, mixed>  $data  username, admin, custom_role_id
     * @return array<string, mixed>
     */
    public function update(string $workspaceId, string $userId, array $data): array
    {
        return $this->client->put(
            'team/' . $this->segment($workspaceId) . '/user/' . $this->segment($userId),
            $data
        );
    }

    /**
     * Remove someone from the Workspace.
     *
     * @return array<mixed>
     */
    public function remove(string $workspaceId, string $userId): array
    {
        return $this->client->delete(
            'team/' . $this->segment($workspaceId) . '/user/' . $this->segment($userId)
        );
    }
}
