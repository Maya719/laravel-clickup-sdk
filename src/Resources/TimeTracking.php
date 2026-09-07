<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Time tracking endpoints (the 2.0 Workspace-scoped API).
 *
 * @see https://developer.clickup.com/reference/gettimeentrieswithinadaterange
 */
class TimeTracking extends Resource
{
    /**
     * Time entries in a date range.
     *
     * Useful filters: start_date, end_date (ms timestamps), assignee,
     * include_task_tags, include_location_names, space_id, folder_id, list_id, task_id.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function all(string $workspaceId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/time_entries', $query),
            'data'
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $workspaceId, string $timerId, array $query = []): array
    {
        return $this->client->get(
            'team/' . $this->segment($workspaceId) . '/time_entries/' . $this->segment($timerId),
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $data   tid, start, duration, description, billable, assignee, tags
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function create(string $workspaceId, array $data, array $query = []): array
    {
        return $this->client->post(
            'team/' . $this->segment($workspaceId) . '/time_entries',
            $data,
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function update(string $workspaceId, string $timerId, array $data, array $query = []): array
    {
        return $this->client->put(
            'team/' . $this->segment($workspaceId) . '/time_entries/' . $this->segment($timerId),
            $data,
            $query
        );
    }

    /**
     * @return array<mixed>
     */
    public function delete(string $workspaceId, string $timerId): array
    {
        return $this->client->delete(
            'team/' . $this->segment($workspaceId) . '/time_entries/' . $this->segment($timerId)
        );
    }

    /**
     * The timer that is currently running, if any.
     *
     * @param  array<string, mixed>  $query  Supports `assignee`.
     * @return array<string, mixed>
     */
    public function running(string $workspaceId, array $query = []): array
    {
        return $this->client->get(
            'team/' . $this->segment($workspaceId) . '/time_entries/current',
            $query
        );
    }

    /**
     * Start a timer, optionally against a task.
     *
     * @param  array<string, mixed>  $data   tid, description, billable, tags
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function start(string $workspaceId, array $data = [], array $query = []): array
    {
        return $this->client->post(
            'team/' . $this->segment($workspaceId) . '/time_entries/start',
            $data,
            $query
        );
    }

    /**
     * Stop the running timer.
     *
     * @return array<string, mixed>
     */
    public function stop(string $workspaceId): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/time_entries/stop');
    }

    /**
     * Every change made to a time entry.
     *
     * @return array<int, mixed>
     */
    public function history(string $workspaceId, string $timerId): array
    {
        return $this->unwrap(
            $this->client->get(
                'team/' . $this->segment($workspaceId) . '/time_entries/' . $this->segment($timerId) . '/history'
            ),
            'data'
        );
    }

    /**
     * All labels applied to time entries in the Workspace.
     *
     * @return array<int, mixed>
     */
    public function tags(string $workspaceId): array
    {
        return $this->unwrap(
            $this->client->get('team/' . $this->segment($workspaceId) . '/time_entries/tags'),
            'data'
        );
    }

    /**
     * @param  array<int, string>                $timerIds
     * @param  array<int, array<string, mixed>>  $tags      Each tag is ['name' => ..., 'tag_bg' => ..., 'tag_fg' => ...].
     * @return array<mixed>
     */
    public function addTags(string $workspaceId, array $timerIds, array $tags): array
    {
        return $this->client->post('team/' . $this->segment($workspaceId) . '/time_entries/tags', [
            'time_entry_ids' => array_values($timerIds),
            'tags' => array_values($tags),
        ]);
    }

    /**
     * @param  array<int, string>                $timerIds
     * @param  array<int, array<string, mixed>>  $tags
     * @return array<mixed>
     */
    public function removeTags(string $workspaceId, array $timerIds, array $tags): array
    {
        return $this->client->request(
            'DELETE',
            'team/' . $this->segment($workspaceId) . '/time_entries/tags',
            [
                'json' => [
                    'time_entry_ids' => array_values($timerIds),
                    'tags' => array_values($tags),
                ],
            ]
        );
    }

    /**
     * Rename a time entry label across the Workspace.
     *
     * @return array<mixed>
     */
    public function renameTag(string $workspaceId, string $name, string $newName, string $tagBg, string $tagFg): array
    {
        return $this->client->put('team/' . $this->segment($workspaceId) . '/time_entries/tags', [
            'name' => $name,
            'new_name' => $newName,
            'tag_bg' => $tagBg,
            'tag_fg' => $tagFg,
        ]);
    }

    /**
     * Legacy per-task time entries.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function forTask(string $taskId, array $query = []): array
    {
        return $this->unwrap(
            $this->client->get('task/' . $this->segment($taskId) . '/time', $query),
            'data'
        );
    }
}
