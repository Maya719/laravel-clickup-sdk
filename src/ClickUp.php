<?php

declare(strict_types=1);

namespace Maya719\ClickUp;

use GuzzleHttp\ClientInterface;
use Maya719\ClickUp\Authorization\OAuth;
use Maya719\ClickUp\Exceptions\ClickUpException;
use Maya719\ClickUp\Http\Client;
use Maya719\ClickUp\Resources;

/**
 * Entry point for the ClickUp API v2.
 *
 * ```php
 * $clickup = new ClickUp('pk_12345_abcdef');
 * $tasks = $clickup->tasks()->all('901300000000');
 * ```
 */
class ClickUp
{
    protected Client $client;

    /** @var array<string, Resources\Resource> */
    protected array $resources = [];

    /**
     * @param  string  $token    Personal API key (pk_...) or an OAuth access token.
     * @param  bool    $isOAuth  Whether $token is an OAuth access token.
     */
    public function __construct(
        string $token = '',
        bool $isOAuth = false,
        string $baseUrl = Client::DEFAULT_BASE_URL,
        ?ClientInterface $http = null,
        protected ?string $defaultListId = null,
        int $retries = 2,
    ) {
        $this->client = new Client($token, $isOAuth, $baseUrl, $http, $retries);
    }

    /**
     * Build a client around an existing transport, e.g. one with test middleware.
     */
    public static function fromClient(Client $client, ?string $defaultListId = null): static
    {
        $instance = new static(defaultListId: $defaultListId);
        $instance->client = $client;
        $instance->resources = [];

        return $instance;
    }

    public function tasks(): Resources\Tasks
    {
        return $this->resource(Resources\Tasks::class);
    }

    public function lists(): Resources\Lists
    {
        return $this->resource(Resources\Lists::class);
    }

    public function folders(): Resources\Folders
    {
        return $this->resource(Resources\Folders::class);
    }

    public function spaces(): Resources\Spaces
    {
        return $this->resource(Resources\Spaces::class);
    }

    public function workspaces(): Resources\Workspaces
    {
        return $this->resource(Resources\Workspaces::class);
    }

    /**
     * Alias of {@see workspaces()}, matching ClickUp's older "team" wording.
     */
    public function teams(): Resources\Workspaces
    {
        return $this->workspaces();
    }

    public function comments(): Resources\Comments
    {
        return $this->resource(Resources\Comments::class);
    }

    public function checklists(): Resources\Checklists
    {
        return $this->resource(Resources\Checklists::class);
    }

    public function customFields(): Resources\CustomFields
    {
        return $this->resource(Resources\CustomFields::class);
    }

    public function tags(): Resources\Tags
    {
        return $this->resource(Resources\Tags::class);
    }

    public function attachments(): Resources\Attachments
    {
        return $this->resource(Resources\Attachments::class);
    }

    public function dependencies(): Resources\Dependencies
    {
        return $this->resource(Resources\Dependencies::class);
    }

    public function goals(): Resources\Goals
    {
        return $this->resource(Resources\Goals::class);
    }

    public function views(): Resources\Views
    {
        return $this->resource(Resources\Views::class);
    }

    public function timeTracking(): Resources\TimeTracking
    {
        return $this->resource(Resources\TimeTracking::class);
    }

    public function webhooks(): Resources\Webhooks
    {
        return $this->resource(Resources\Webhooks::class);
    }

    public function members(): Resources\Members
    {
        return $this->resource(Resources\Members::class);
    }

    public function users(): Resources\Users
    {
        return $this->resource(Resources\Users::class);
    }

    public function guests(): Resources\Guests
    {
        return $this->resource(Resources\Guests::class);
    }

    public function userGroups(): Resources\UserGroups
    {
        return $this->resource(Resources\UserGroups::class);
    }

    public function authorizedUser(): Resources\AuthorizedUser
    {
        return $this->resource(Resources\AuthorizedUser::class);
    }

    /**
     * OAuth helper bound to this client's transport.
     */
    public function oauth(string $clientId, string $clientSecret): OAuth
    {
        return new OAuth($this->client, $clientId, $clientSecret);
    }

    /**
     * The underlying transport, for endpoints this SDK does not wrap yet.
     */
    public function http(): Client
    {
        return $this->client;
    }

    /**
     * Rate limit state advertised by the most recent response.
     *
     * @return array{limit: int|null, remaining: int|null, reset: int|null}
     */
    public function rateLimit(): array
    {
        return $this->client->rateLimit();
    }

    /**
     * Swap credentials at runtime, e.g. after completing an OAuth exchange.
     */
    public function withToken(string $token, ?bool $isOAuth = null): static
    {
        $this->client->withToken($token, $isOAuth);

        return $this;
    }

    public function defaultListId(): ?string
    {
        return $this->defaultListId;
    }

    public function setDefaultListId(?string $listId): static
    {
        $this->defaultListId = $listId;

        return $this;
    }

    /**
     * Create a task in the configured default List.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createTask(array $data, ?string $listId = null): array
    {
        return $this->tasks()->create($this->resolveListId($listId), $data);
    }

    /**
     * Read tasks from the configured default List.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, mixed>
     */
    public function listTasks(array $query = [], ?string $listId = null): array
    {
        return $this->tasks()->all($this->resolveListId($listId), $query);
    }

    protected function resolveListId(?string $listId): string
    {
        $resolved = $listId ?? $this->defaultListId;

        if ($resolved === null || $resolved === '') {
            throw new ClickUpException(
                'No List id was given and no default is configured. Pass one explicitly or set CLICKUP_LIST_ID.'
            );
        }

        return $resolved;
    }

    /**
     * @template T of Resources\Resource
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function resource(string $class): Resources\Resource
    {
        /** @var T */
        return $this->resources[$class] ??= new $class($this->client);
    }
}
