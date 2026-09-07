<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

use Generator;

/**
 * Tasks endpoints.
 *
 * @see https://developer.clickup.com/reference/gettasks
 */
class Tasks extends Resource
{
    /**
     * One page of tasks in a List.
     *
     * Useful filters: archived, page, order_by, reverse, subtasks, statuses[],
     * include_closed, assignees[], tags[], due_date_gt, due_date_lt.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function all(string $listId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('list/' . $this->segment($listId) . '/task', $query),
            'tasks'
        );
    }

    /**
     * The raw paginated response, including the `last_page` flag.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function page(string $listId, int $page = 0, array $query = []): array
    {
        return $this->client->get(
            'list/' . $this->segment($listId) . '/task',
            array_merge($query, ['page' => $page])
        );
    }

    /**
     * Lazily walk every page of a List, yielding one task at a time.
     *
     * ClickUp returns 100 tasks per page and flags the final page with `last_page`.
     *
     * @param  array<string, mixed>  $query
     * @return Generator<int, array<string, mixed>>
     */
    public function cursor(string $listId, array $query = []): Generator
    {
        $page = (int) ($query['page'] ?? 0);

        do {
            $response = $this->page($listId, $page, $query);
            $tasks = $this->unwrap($response, 'tasks');

            foreach ($tasks as $task) {
                yield $task;
            }

            $page++;
        } while ($tasks !== [] && ! ($response['last_page'] ?? false));
    }

    /**
     * A single task.
     *
     * Pass `['custom_task_ids' => true, 'team_id' => $workspaceId]` to look a task
     * up by its custom id.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $taskId, array $query = []): array
    {
        return $this->client->get('task/' . $this->segment($taskId), $query);
    }

    /**
     * @param  array<string, mixed>  $data   name, description, assignees, status, priority, due_date, ...
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function create(string $listId, array $data, array $query = []): array
    {
        return $this->client->post('list/' . $this->segment($listId) . '/task', $data, $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function update(string $taskId, array $data, array $query = []): array
    {
        return $this->client->put('task/' . $this->segment($taskId), $data, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function delete(string $taskId, array $query = []): array
    {
        return $this->client->delete('task/' . $this->segment($taskId), $query);
    }

    /**
     * Tasks across an entire Workspace, filtered by the given query.
     *
     * Accepts space_ids[], project_ids[], list_ids[], statuses[], assignees[], tags[].
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function filterByWorkspace(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/task', $query),
            'tasks'
        );
    }

    /**
     * Lazily walk every page of a Workspace-wide task filter.
     *
     * @param  array<string, mixed>  $query
     * @return Generator<int, array<string, mixed>>
     */
    public function cursorByWorkspace(string $workspaceId, array $query = []): Generator
    {
        $page = (int) ($query['page'] ?? 0);

        do {
            $response = $this->client->get(
                'team/' . $this->segment($workspaceId) . '/task',
                array_merge($query, ['page' => $page])
            );
            $tasks = $this->unwrap($response, 'tasks');

            foreach ($tasks as $task) {
                yield $task;
            }

            $page++;
        } while ($tasks !== [] && ! ($response['last_page'] ?? false));
    }

    /**
     * How long a task has spent in each status.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function timeInStatus(string $taskId, array $query = []): array
    {
        return $this->client->get('task/' . $this->segment($taskId) . '/time_in_status', $query);
    }

    /**
     * Time-in-status for up to 100 tasks at once.
     *
     * Unlike the array filters elsewhere in the API, this endpoint expects the ids
     * as repeated plain keys (`?task_ids=a&task_ids=b`), not as `task_ids[]`, so
     * the query string is assembled here instead of by the transport.
     *
     * @param  array<int, string>    $taskIds
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function bulkTimeInStatus(array $taskIds, array $query = []): array
    {
        $pairs = array_map(
            static fn (string $id): string => 'task_ids=' . rawurlencode($id),
            array_values($taskIds)
        );

        if ($query !== []) {
            $pairs[] = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->client->request('GET', 'task/bulk_time_in_status/task_ids', [
            'query' => implode('&', array_filter($pairs)),
        ]);
    }

    /**
     * Create a task from a saved task template.
     *
     * @param  array<string, mixed>  $data   Must include at least `name`.
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function createFromTemplate(string $listId, string $templateId, array $data, array $query = []): array
    {
        return $this->client->post(
            'list/' . $this->segment($listId) . '/taskTemplate/' . $this->segment($templateId),
            $data,
            $query
        );
    }

    /**
     * Merge $sourceTaskId into $targetTaskId.
     *
     * @return array<string, mixed>
     */
    public function merge(string $targetTaskId, string $sourceTaskId): array
    {
        return $this->client->post('task/' . $this->segment($targetTaskId) . '/merge', [
            'source_task_id' => $sourceTaskId,
        ]);
    }
}
