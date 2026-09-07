<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

use Generator;

/**
 * View endpoints for every level of the hierarchy.
 *
 * @see https://developer.clickup.com/reference/getteamviews
 */
class Views extends Resource
{
    /**
     * @return array<int, mixed>
     */
    public function forWorkspace(string $workspaceId): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/view'),
            'views'
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function forSpace(string $spaceId): array
    {
        return $this->unwrap($this->client->get('space/' . $this->segment($spaceId) . '/view'), 'views');
    }

    /**
     * @return array<int, mixed>
     */
    public function forFolder(string $folderId): array
    {
        return $this->unwrap($this->client->get('folder/' . $this->segment($folderId) . '/view'), 'views');
    }

    /**
     * @return array<int, mixed>
     */
    public function forList(string $listId): array
    {
        return $this->unwrap($this->client->get('list/' . $this->segment($listId) . '/view'), 'views');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $viewId): array
    {
        return $this->client->get('view/' . $this->segment($viewId));
    }

    /**
     * @param  array<string, mixed>  $data  name, type, grouping, divide, sorting, filters, columns, settings
     * @return array<string, mixed>
     */
    public function createForWorkspace(string $workspaceId, array $data): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/view', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForSpace(string $spaceId, array $data): array
    {
        return $this->client->post('space/' . $this->segment($spaceId) . '/view', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForFolder(string $folderId, array $data): array
    {
        return $this->client->post('folder/' . $this->segment($folderId) . '/view', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForList(string $listId, array $data): array
    {
        return $this->client->post('list/' . $this->segment($listId) . '/view', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $viewId, array $data): array
    {
        return $this->client->put('view/' . $this->segment($viewId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $viewId): array
    {
        return $this->client->delete('view/' . $this->segment($viewId));
    }

    /**
     * One page of the tasks visible in a View.
     *
     * @return array<int, mixed>
     */
    public function tasks(string $viewId, int $page = 0): array
    {
        return $this->unwrap(
            $this->client->get('view/' . $this->segment($viewId) . '/task', ['page' => $page]),
            'tasks'
        );
    }

    /**
     * Lazily walk every page of tasks in a View.
     *
     * @return Generator<int, array<string, mixed>>
     */
    public function cursor(string $viewId): Generator
    {
        $page = 0;

        do {
            $response = $this->client->get('view/' . $this->segment($viewId) . '/task', ['page' => $page]);
            $tasks = $this->unwrap($response, 'tasks');

            foreach ($tasks as $task) {
                yield $task;
            }

            $page++;
        } while ($tasks !== [] && ! ($response['last_page'] ?? false));
    }
}
