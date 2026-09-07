<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Folder endpoints.
 *
 * @see https://developer.clickup.com/reference/getfolders
 */
class Folders extends Resource
{
    /**
     * @param  array<string, mixed>  $query  Supports `archived`.
     * @return array<int, mixed>
     */
    public function all(string $spaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('space/' . $this->segment($spaceId) . '/folder', $query),
            'folders'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $folderId): array
    {
        return $this->client->get('folder/' . $this->segment($folderId));
    }

    /**
     * @param  array<string, mixed>  $data  Requires `name`.
     * @return array<string, mixed>
     */
    public function create(string $spaceId, array $data): array
    {
        return $this->client->post('space/' . $this->segment($spaceId) . '/folder', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $folderId, array $data): array
    {
        return $this->client->put('folder/' . $this->segment($folderId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $folderId): array
    {
        return $this->client->delete('folder/' . $this->segment($folderId));
    }

    /**
     * Create a Folder from a saved Folder template.
     *
     * @param  array<string, mixed>  $data  Requires `name`; accepts `options`.
     * @return array<string, mixed>
     */
    public function createFromTemplate(string $spaceId, string $templateId, array $data): array
    {
        return $this->client->post(
            'space/' . $this->segment($spaceId) . '/folder_template/' . $this->segment($templateId),
            $data
        );
    }
}
