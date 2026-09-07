<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Authorization;

use Maya719\ClickUp\Exceptions\ClickUpException;
use Maya719\ClickUp\Http\Client;

/**
 * ClickUp OAuth 2.0 helper.
 *
 * ClickUp issues access tokens that do not expire and does not return a refresh
 * token, so the flow is: redirect the user to {@see authorizationUrl()}, then
 * exchange the returned `code` with {@see accessToken()} and store the result.
 *
 * @see https://developer.clickup.com/docs/authentication
 */
class OAuth
{
    public const AUTHORIZE_URL = 'https://app.clickup.com/api';

    public function __construct(
        protected readonly Client $client,
        protected readonly string $clientId,
        protected readonly string $clientSecret,
    ) {
    }

    /**
     * The URL to send the user to in order to approve your app.
     *
     * `$state` is echoed back to your redirect URI; use it for CSRF protection.
     */
    public function authorizationUrl(string $redirectUri, ?string $state = null): string
    {
        $query = [
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
        ];

        if ($state !== null && $state !== '') {
            $query['state'] = $state;
        }

        return self::AUTHORIZE_URL . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange the `code` from the redirect for an access token.
     *
     * @return array<string, mixed> Typically ['access_token' => ..., 'token_type' => 'Bearer'].
     */
    public function accessToken(string $code): array
    {
        // ClickUp reads these as query parameters, not as a JSON body.
        return $this->client->post('oauth/token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
        ]);
    }

    /**
     * Exchange the code and return just the access token string.
     */
    public function accessTokenString(string $code): string
    {
        $response = $this->accessToken($code);
        $token = $response['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new ClickUpException(
                'ClickUp did not return an access_token for the supplied authorization code.',
                context: $response,
            );
        }

        return $token;
    }
}
