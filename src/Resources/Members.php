<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Read-only membership endpoints.
 *
 * @see https://developer.clickup.com/reference/gettaskmembers
 */
class Members extends Resource
{
    /**
     * People with access to a task.
     *
     * @return array<int, mixed>
     */
    public function forTask(string $taskId): array
    {
        return $this->unwrap(
            $this->client->get('task/' . $this->segment($taskId) . '/member'),
            'members'
        );
    }

    /**
     * People with access to a List.
     *
     * @return array<int, mixed>
     */
    public function forList(string $listId): array
    {
        return $this->unwrap(
            $this->client->get('list/' . $this->segment($listId) . '/member'),
            'members'
        );
    }
}
