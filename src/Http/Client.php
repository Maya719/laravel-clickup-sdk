<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Maya719\ClickUp\Exceptions\AuthenticationException;
use Maya719\ClickUp\Exceptions\AuthorizationException;
use Maya719\ClickUp\Exceptions\ClickUpException;
use Maya719\ClickUp\Exceptions\NotFoundException;
use Maya719\ClickUp\Exceptions\RateLimitException;
use Maya719\ClickUp\Exceptions\ServerException;
use Maya719\ClickUp\Exceptions\TransportException;
use Maya719\ClickUp\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin transport layer over Guzzle: signs requests, decodes JSON and turns
 * ClickUp's error payloads into typed exceptions.
 */
class Client
{
    public const DEFAULT_BASE_URL = 'https://api.clickup.com/api/v2/';

    protected ClientInterface $http;

    protected string $baseUrl;

    protected ?ResponseInterface $lastResponse = null;

    /**
     * @param  string  $token    Personal API key (pk_...) or an OAuth access token.
     * @param  bool    $isOAuth  Whether $token is an OAuth access token. Only affects
     *                           diagnostics: ClickUp accepts both as a bare Authorization header.
     * @param  int     $retries  How many times to retry throttled / transient failures.
     */
    public function __construct(
        protected string $token,
        protected bool $isOAuth = false,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?ClientInterface $http = null,
        protected int $retries = 2,
        protected float $timeout = 30.0,
    ) {
        $this->token = $this->normalizeToken($token);
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->http = $http ?? new GuzzleClient([
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::CONNECT_TIMEOUT => 10.0,
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->request('GET', $uri, ['query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function post(string $uri, array $payload = [], array $query = []): array
    {
        return $this->request('POST', $uri, ['json' => $payload, 'query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function put(string $uri, array $payload = [], array $query = []): array
    {
        return $this->request('PUT', $uri, ['json' => $payload, 'query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function delete(string $uri, array $query = []): array
    {
        return $this->request('DELETE', $uri, ['query' => $query]);
    }

    /**
     * Upload a file with a multipart request (used by task attachments).
     *
     * @param  array<int, array<string, mixed>>  $multipart
     * @param  array<string, mixed>              $query
     * @return array<mixed>
     */
    public function multipart(string $uri, array $multipart, array $query = []): array
    {
        return $this->request('POST', $uri, ['multipart' => $multipart, 'query' => $query]);
    }

    /**
     * Issue a request, retrying throttled and transient failures.
     *
     * @param  array<string, mixed>  $options
     * @return array<mixed>
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        $options = $this->prepareOptions($options);
        $url = $this->resolveUrl($uri);
        $attempt = 0;

        while (true) {
            try {
                $response = $this->http->request($method, $url, $options);
            } catch (ConnectException $e) {
                if ($attempt < $this->retries) {
                    $this->sleep($this->backoff(++$attempt));

                    continue;
                }

                throw new TransportException(
                    'Could not reach the ClickUp API: ' . $e->getMessage(),
                    previous: $e,
                );
            } catch (GuzzleException $e) {
                throw new TransportException(
                    'ClickUp request failed: ' . $e->getMessage(),
                    previous: $e,
                );
            }

            $this->lastResponse = $response;
            $status = $response->getStatusCode();

            if ($this->shouldRetry($status) && $attempt < $this->retries) {
                $this->sleep($this->retryDelay($response, ++$attempt));

                continue;
            }

            if ($status >= 400) {
                throw $this->toException($response);
            }

            return $this->decode($response);
        }
    }

    /**
     * The raw PSR-7 response for the most recent request, if any.
     */
    public function lastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Rate limit state advertised by the most recent response.
     *
     * @return array{limit: int|null, remaining: int|null, reset: int|null}
     */
    public function rateLimit(): array
    {
        return [
            'limit' => $this->headerInt($this->lastResponse, 'X-RateLimit-Limit'),
            'remaining' => $this->headerInt($this->lastResponse, 'X-RateLimit-Remaining'),
            'reset' => $this->headerInt($this->lastResponse, 'X-RateLimit-Reset'),
        ];
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isOAuth(): bool
    {
        return $this->isOAuth;
    }

    /**
     * Swap the credentials used by subsequent requests (e.g. after an OAuth exchange).
     */
    public function withToken(string $token, ?bool $isOAuth = null): static
    {
        $this->token = $this->normalizeToken($token);

        if ($isOAuth !== null) {
            $this->isOAuth = $isOAuth;
        }

        return $this;
    }

    /**
     * Tolerate a token pasted with the "Bearer " prefix; ClickUp wants it bare.
     */
    protected function normalizeToken(string $token): string
    {
        return (string) preg_replace('/^Bearer\s+/i', '', trim($token));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function prepareOptions(array $options): array
    {
        $options['headers'] = array_merge([
            'Authorization' => $this->token,
            'Accept' => 'application/json',
            'User-Agent' => 'maya719-clickup-php',
        ], $options['headers'] ?? []);

        $options[RequestOptions::HTTP_ERRORS] = false;

        if (isset($options['query']) && is_array($options['query'])) {
            $query = $this->buildQuery($options['query']);

            if ($query === '') {
                unset($options['query']);
            } else {
                $options['query'] = $query;
            }
        }

        // A multipart body must not also carry a JSON body.
        if (isset($options['multipart'])) {
            unset($options['json']);
        }

        return $options;
    }

    /**
     * Build the query string in the shape ClickUp's parser expects.
     *
     * Guzzle would serialise a list as `assignees[0]=1`, but ClickUp's array
     * filters only match the repeated `assignees[]=1&assignees[]=2` form, so the
     * string is assembled here rather than handed to http_build_query.
     *
     * @param  array<string, mixed>  $query
     */
    protected function buildQuery(array $query): string
    {
        $pairs = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                // Nested/associative values keep PHP's bracket notation.
                if (! array_is_list($value)) {
                    $nested = http_build_query([$key => $value], '', '&', PHP_QUERY_RFC3986);

                    if ($nested !== '') {
                        $pairs[] = $nested;
                    }

                    continue;
                }

                $name = str_ends_with((string) $key, '[]') ? (string) $key : $key . '[]';

                foreach ($value as $item) {
                    $pairs[] = rawurlencode($name) . '=' . rawurlencode($this->stringify($item));
                }

                continue;
            }

            $pairs[] = rawurlencode((string) $key) . '=' . rawurlencode($this->stringify($value));
        }

        return implode('&', $pairs);
    }

    /**
     * Guzzle serialises `true` as "1"; ClickUp's filters expect "true"/"false".
     */
    protected function stringify(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }

    protected function resolveUrl(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        return $this->baseUrl . ltrim($uri, '/');
    }

    protected function shouldRetry(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    /**
     * Prefer the window ClickUp advertises, otherwise fall back to exponential backoff.
     */
    protected function retryDelay(ResponseInterface $response, int $attempt): float
    {
        $retryAfter = $this->headerInt($response, 'Retry-After');

        if ($retryAfter !== null && $retryAfter > 0) {
            return (float) min($retryAfter, 60);
        }

        if ($response->getStatusCode() === 429) {
            $reset = $this->headerInt($response, 'X-RateLimit-Reset');

            if ($reset !== null && ($wait = $reset - time()) > 0) {
                return (float) min($wait, 60);
            }
        }

        return $this->backoff($attempt);
    }

    protected function backoff(int $attempt): float
    {
        return min(2 ** ($attempt - 1), 8) + (random_int(0, 250) / 1000);
    }

    protected function sleep(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }

    /**
     * @return array<mixed>
     */
    protected function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        // 204s and several DELETE endpoints answer with an empty body.
        if (trim($body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClickUpException(
                'ClickUp returned a response that is not valid JSON.',
                $response->getStatusCode(),
                previous: $e,
            );
        }

        return is_array($decoded) ? $decoded : ['data' => $decoded];
    }

    protected function toException(ResponseInterface $response): ClickUpException
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $payload = json_decode($body, true);
        $payload = is_array($payload) ? $payload : [];

        // ClickUp errors look like {"err": "Team not authorized", "ECODE": "OAUTH_027"}.
        $message = $payload['err'] ?? $payload['error'] ?? $payload['message'] ?? null;
        $message = is_string($message) && $message !== ''
            ? $message
            : ($body !== '' ? mb_substr($body, 0, 500) : 'ClickUp returned HTTP ' . $status . '.');

        $code = $payload['ECODE'] ?? $payload['ecode'] ?? null;
        $code = is_string($code) ? $code : null;

        if ($status === 429) {
            return new RateLimitException(
                $message,
                $status,
                $code,
                $payload,
                $this->headerInt($response, 'Retry-After'),
                $this->headerInt($response, 'X-RateLimit-Limit'),
                $this->headerInt($response, 'X-RateLimit-Remaining'),
                $this->headerInt($response, 'X-RateLimit-Reset'),
            );
        }

        return match (true) {
            $status === 401 => new AuthenticationException($this->authMessage($message), $status, $code, $payload),
            $status === 403 => new AuthorizationException($message, $status, $code, $payload),
            $status === 404 => new NotFoundException($message, $status, $code, $payload),
            $status === 400, $status === 422 => new ValidationException($message, $status, $code, $payload),
            $status >= 500 => new ServerException($message, $status, $code, $payload),
            default => new ClickUpException($message, $status, $code, $payload),
        };
    }

    protected function authMessage(string $message): string
    {
        if ($this->token === '') {
            return $message . ' (no ClickUp token was configured — set CLICKUP_API_KEY).';
        }

        return $message . ($this->isOAuth
            ? ' (check that the OAuth access token is still valid and has not been revoked).'
            : ' (check that CLICKUP_API_KEY holds a personal token beginning with "pk_").');
    }

    protected function headerInt(?ResponseInterface $response, string $header): ?int
    {
        if ($response === null || ! $response->hasHeader($header)) {
            return null;
        }

        $value = trim($response->getHeaderLine($header));

        return is_numeric($value) ? (int) $value : null;
    }
}
