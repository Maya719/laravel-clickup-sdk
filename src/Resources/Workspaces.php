<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Workspace endpoints.
 *
 * ClickUp's API still calls a Workspace a "team", so every path below uses /team.
 *
 * @see https://developer.clickup.com/reference/getauthorizedteams
 */
class Workspaces extends Resource
{
    /**
     * Every Workspace the token can access.
     *
     * @return array<int, mixed>
     */
    public function all(): array
    {
        return $this->unwrap($this->client->get('team'), 'teams');
    }

    /**
     * Seat usage (members vs. guests) for a Workspace.
     *
     * @return array<string, mixed>
     */
    public function seats(string $workspaceId): array
    {
        return $this->client->get('team/' . $this->segment($workspaceId) . '/seats');
    }

    /**
     * The Workspace's current plan.
     *
     * @return array<string, mixed>
     */
    public function plan(string $workspaceId): array
    {
        return $this->client->get('team/' . $this->segment($workspaceId) . '/plan');
    }

    /**
     * Custom roles defined in the Workspace.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function customRoles(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/customroles', $query),
            'custom_roles'
        );
    }

    /**
     * Everything shared with the token that lives outside its own hierarchy.
     *
     * @return array<string, mixed>
     */
    public function sharedHierarchy(string $workspaceId): array
    {
        return $this->client->get('team/' . $this->segment($workspaceId) . '/shared');
    }

    /**
     * Saved task templates.
     *
     * @return array<int, mixed>
     */
    public function taskTemplates(string $workspaceId, int $page = 0): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/taskTemplate', ['page' => $page]),
            'templates'
        );
    }
}
