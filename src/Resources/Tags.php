<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Space tag endpoints, plus adding and removing tags on tasks.
 *
 * @see https://developer.clickup.com/reference/getspacetags
 */
class Tags extends Resource
{
    /**
     * @return array<int, mixed>
     */
    public function all(string $spaceId): array
    {
        return $this->unwrap(
            $this->client->get('space/' . $this->segment($spaceId) . '/tag'),
            'tags'
        );
    }

    /**
     * @param  array<string, mixed>  $tag  name, tag_fg, tag_bg
     * @return array<mixed>
     */
    public function create(string $spaceId, array $tag): array
    {
        return $this->client->post('space/' . $this->segment($spaceId) . '/tag', ['tag' => $tag]);
    }

    /**
     * @param  array<string, mixed>  $tag  The replacement name and colours.
     * @return array<mixed>
     */
    public function update(string $spaceId, string $tagName, array $tag): array
    {
        return $this->client->put(
            'space/' . $this->segment($spaceId) . '/tag/' . $this->segment($tagName),
            ['tag' => $tag]
        );
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $spaceId, string $tagName): array
    {
        return $this->client->delete(
            'space/' . $this->segment($spaceId) . '/tag/' . $this->segment($tagName)
        );
    }

    /**
     * @param  array<string, mixed>  $query  Supports `custom_task_ids` + `team_id`.
     * @return array<mixed>
     */
    public function addToTask(string $taskId, string $tagName, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/tag/' . $this->segment($tagName),
            [],
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function removeFromTask(string $taskId, string $tagName, array $query = []): array
    {
        return $this->client->delete(
            'task/' . $this->segment($taskId) . '/tag/' . $this->segment($tagName),
            $query
        );
    }
}
