<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Maya719\ClickUp\ClickUp;
use Maya719\ClickUp\Http\Client;
use Psr\Http\Message\RequestInterface;

/**
 * Builds a ClickUp client backed by Guzzle's MockHandler and records the
 * requests it sends so tests can assert on method, path, query and body.
 */
trait MocksClickUp
{
    /** @var array<int, array{request: RequestInterface, options: array<string, mixed>}> */
    protected array $recorded = [];

    /**
     * @param  array<int, Response|\Throwable>  $responses
     */
    protected function mockClient(array $responses, string $token = 'pk_test', int $retries = 0): Client
    {
        $this->recorded = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->recorded));

        return new Client(
            token: $token,
            http: new GuzzleClient(['handler' => $stack, 'http_errors' => false]),
            retries: $retries,
        );
    }

    /**
     * @param  array<int, Response|\Throwable>  $responses
     */
    protected function mockClickUp(array $responses, ?string $defaultListId = null): ClickUp
    {
        return ClickUp::fromClient($this->mockClient($responses), $defaultListId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function jsonResponse(array $payload, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    protected function lastRequest(): RequestInterface
    {
        $entry = end($this->recorded);

        if ($entry === false) {
            $this->fail('No HTTP request was recorded.');
        }

        return $entry['request'];
    }

    protected function requestAt(int $index): RequestInterface
    {
        $this->assertArrayHasKey($index, $this->recorded, "No request was recorded at index {$index}.");

        return $this->recorded[$index]['request'];
    }

    protected function requestCount(): int
    {
        return count($this->recorded);
    }

    /**
     * The decoded JSON body of the most recent request.
     *
     * @return array<string, mixed>
     */
    protected function lastRequestBody(): array
    {
        $body = (string) $this->lastRequest()->getBody();

        if (trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
