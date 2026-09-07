<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * List endpoints.
 *
 * @see https://developer.clickup.com/reference/getlists
 */
class Lists extends Resource
{
    /**
     * Lists inside a Folder.
     *
     * @param  array<string, mixed>  $query  Supports `archived`.
     * @return array<int, mixed>
     */
    public function all(string $folderId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('folder/' . $this->segment($folderId) . '/list', $query),
            'lists'
        );
    }

    /**
     * Folderless Lists that sit directly in a Space.
     *
     * @param  array<string, mixed>  $query  Supports `archived`.
     * @return array<int, mixed>
     */
    public function folderless(string $spaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('space/' . $this->segment($spaceId) . '/list', $query),
            'lists'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $listId): array
    {
        return $this->client->get('list/' . $this->segment($listId));
    }

    /**
     * @param  array<string, mixed>  $data  name, content, due_date, assignee, status, priority
     * @return array<string, mixed>
     */
    public function create(string $folderId, array $data): array
    {
        return $this->client->post('folder/' . $this->segment($folderId) . '/list', $data);
    }

    /**
     * Create a List directly in a Space, with no Folder.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createFolderless(string $spaceId, array $data): array
    {
        return $this->client->post('space/' . $this->segment($spaceId) . '/list', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $listId, array $data): array
    {
        return $this->client->put('list/' . $this->segment($listId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $listId): array
    {
        return $this->client->delete('list/' . $this->segment($listId));
    }

    /**
     * Add an existing task to this List (Tasks in Multiple Lists must be enabled).
     *
     * @return array<mixed>
     */
    public function addTask(string $listId, string $taskId): array
    {
        return $this->client->post(
            'list/' . $this->segment($listId) . '/task/' . $this->segment($taskId)
        );
    }

    /**
     * Remove a task from an additional List.
     *
     * @return array<mixed>
     */
    public function removeTask(string $listId, string $taskId): array
    {
        return $this->client->delete(
            'list/' . $this->segment($listId) . '/task/' . $this->segment($taskId)
        );
    }

    /**
     * People with access to the List.
     *
     * @return array<int, mixed>
     */
    public function members(string $listId): array
    {
        return $this->unwrap(
            $this->client->get('list/' . $this->segment($listId) . '/member'),
            'members'
        );
    }

    /**
     * Create a List from a saved List template inside a Folder.
     *
     * @param  array<string, mixed>  $data  Requires `name`.
     * @return array<string, mixed>
     */
    public function createFromTemplateInFolder(string $folderId, string $templateId, array $data): array
    {
        return $this->client->post(
            'folder/' . $this->segment($folderId) . '/list_template/' . $this->segment($templateId),
            $data
        );
    }

    /**
     * Create a List from a saved List template directly in a Space.
     *
     * @param  array<string, mixed>  $data  Requires `name`.
     * @return array<string, mixed>
     */
    public function createFromTemplateInSpace(string $spaceId, string $templateId, array $data): array
    {
        return $this->client->post(
            'space/' . $this->segment($spaceId) . '/list_template/' . $this->segment($templateId),
            $data
        );
    }
}
