<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Task dependency and task link endpoints.
 *
 * @see https://developer.clickup.com/reference/adddependency
 */
class Dependencies extends Resource
{
    /**
     * Mark $taskId as waiting on $dependsOn.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function addWaitingOn(string $taskId, string $dependsOn, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/dependency',
            ['depends_on' => $dependsOn],
            $query
        );
    }

    /**
     * Mark $taskId as blocking $dependencyOf.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function addBlocking(string $taskId, string $dependencyOf, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/dependency',
            ['dependency_of' => $dependencyOf],
            $query
        );
    }

    /**
     * Remove a dependency. Exactly one of $dependsOn / $dependencyOf must be given.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function remove(
        string $taskId,
        ?string $dependsOn = null,
        ?string $dependencyOf = null,
        array $query = []
    ): array {
        return $this->client->delete(
            'task/' . $this->segment($taskId) . '/dependency',
            array_merge($query, array_filter([
                'depends_on' => $dependsOn,
                'dependency_of' => $dependencyOf,
            ], static fn (?string $value): bool => $value !== null))
        );
    }

    /**
     * Link two tasks together (a plain link, not a blocking dependency).
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function link(string $taskId, string $linksTo, array $query = []): array
    {
        return $this->client->post(
            'task/' . $this->segment($taskId) . '/link/' . $this->segment($linksTo),
            [],
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function unlink(string $taskId, string $linksTo, array $query = []): array
    {
        return $this->client->delete(
            'task/' . $this->segment($taskId) . '/link/' . $this->segment($linksTo),
            $query
        );
    }
}
