<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

/**
 * Details about the token currently in use.
 *
 * @see https://developer.clickup.com/reference/getauthorizeduser
 */
class AuthorizedUser extends Resource
{
    /**
     * The user the token belongs to.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $response = $this->client->get('user');

        return is_array($response['user'] ?? null) ? $response['user'] : $response;
    }

    /**
     * Every Workspace the token can reach.
     *
     * @return array<int, mixed>
     */
    public function workspaces(): array
    {
        return $this->unwrap($this->client->get('team'), 'teams');
    }
}
