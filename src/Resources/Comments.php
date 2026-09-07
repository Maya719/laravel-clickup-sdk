<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Comment endpoints for tasks, Lists, Views and threaded replies.
 *
 * @see https://developer.clickup.com/reference/gettaskcomments
 */
class Comments extends Resource
{
    /**
     * @param  array<string, mixed>  $query  Supports `start` / `start_id` for paging.
     * @return array<int, mixed>
     */
    public function forTask(string $taskId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('task/' . $this->segment($taskId) . '/comment', $query),
            'comments'
        );
    }

    /**
     * @param  array<string, mixed>  $data   Requires `comment_text`; accepts `assignee`, `notify_all`.
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function createForTask(string $taskId, array $data, array $query = []): array
    {
        return $this->client->post('task/' . $this->segment($taskId) . '/comment', $data, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function forList(string $listId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('list/' . $this->segment($listId) . '/comment', $query),
            'comments'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForList(string $listId, array $data): array
    {
        return $this->client->post('list/' . $this->segment($listId) . '/comment', $data);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function forView(string $viewId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('view/' . $this->segment($viewId) . '/comment', $query),
            'comments'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForView(string $viewId, array $data): array
    {
        return $this->client->post('view/' . $this->segment($viewId) . '/comment', $data);
    }

    /**
     * Replies in a comment thread.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function replies(string $commentId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('comment/' . $this->segment($commentId) . '/reply', $query),
            'comments'
        );
    }

    /**
     * @param  array<string, mixed>  $data  Requires `comment_text`.
     * @return array<string, mixed>
     */
    public function reply(string $commentId, array $data): array
    {
        return $this->client->post('comment/' . $this->segment($commentId) . '/reply', $data);
    }

    /**
     * @param  array<string, mixed>  $data  comment_text, assignee, resolved
     * @return array<mixed>
     */
    public function update(string $commentId, array $data): array
    {
        return $this->client->put('comment/' . $this->segment($commentId), $data);
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $commentId): array
    {
        return $this->client->delete('comment/' . $this->segment($commentId));
    }
}
