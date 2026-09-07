<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Maya719\ClickUp\Exceptions\AuthenticationException;
use Maya719\ClickUp\Exceptions\NotFoundException;
use Maya719\ClickUp\Exceptions\RateLimitException;
use Maya719\ClickUp\Exceptions\ServerException;
use Maya719\ClickUp\Exceptions\TransportException;
use Maya719\ClickUp\Exceptions\ValidationException;
use Maya719\ClickUp\Http\Client;
use Maya719\ClickUp\Tests\MocksClickUp;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    use MocksClickUp;

    public function test_it_sends_the_token_as_a_bare_authorization_header(): void
    {
        $client = $this->mockClient([$this->jsonResponse(['id' => '1'])]);

        $client->get('task/1');

        $this->assertSame('pk_test', $this->lastRequest()->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $this->lastRequest()->getHeaderLine('Accept'));
    }

    public function test_it_strips_a_pasted_bearer_prefix(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])], token: 'Bearer  pk_123');

        $client->get('user');

        $this->assertSame('pk_123', $this->lastRequest()->getHeaderLine('Authorization'));
    }

    public function test_it_resolves_relative_paths_against_the_base_url(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('/list/42/task');

        $this->assertSame(
            'https://api.clickup.com/api/v2/list/42/task',
            (string) $this->lastRequest()->getUri()
        );
    }

    public function test_it_encodes_booleans_as_true_and_false(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/42/task', ['archived' => false, 'subtasks' => true]);

        $query = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('archived=false', $query);
        $this->assertStringContainsString('subtasks=true', $query);
    }

    public function test_it_expands_list_values_into_repeated_keys(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/42/task', ['assignees' => [11, 22]]);

        $query = urldecode($this->lastRequest()->getUri()->getQuery());
        $this->assertStringContainsString('assignees[]=11', $query);
        $this->assertStringContainsString('assignees[]=22', $query);
    }

    public function test_it_drops_null_query_values(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/42/task', ['page' => 0, 'status' => null]);

        $this->assertSame('page=0', $this->lastRequest()->getUri()->getQuery());
    }

    public function test_it_returns_an_empty_array_for_an_empty_body(): void
    {
        $client = $this->mockClient([new Response(200, [], '')]);

        $this->assertSame([], $client->delete('task/1'));
    }

    public function test_it_maps_status_codes_to_typed_exceptions(): void
    {
        $cases = [
            401 => AuthenticationException::class,
            404 => NotFoundException::class,
            400 => ValidationException::class,
            422 => ValidationException::class,
            500 => ServerException::class,
        ];

        foreach ($cases as $status => $expected) {
            $client = $this->mockClient([
                $this->jsonResponse(['err' => 'Boom', 'ECODE' => 'X_001'], $status),
            ]);

            try {
                $client->get('task/1');
                $this->fail("Expected {$expected} for HTTP {$status}.");
            } catch (\Maya719\ClickUp\Exceptions\ClickUpException $e) {
                $this->assertInstanceOf($expected, $e);
                $this->assertSame($status, $e->status());
                $this->assertSame('X_001', $e->errorCode());
                $this->assertStringContainsString('Boom', $e->getMessage());
            }
        }
    }

    public function test_the_401_message_explains_which_credential_to_check(): void
    {
        $client = $this->mockClient([$this->jsonResponse(['err' => 'Token invalid'], 401)]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/pk_/');

        $client->get('user');
    }

    public function test_it_exposes_rate_limit_details_on_a_429(): void
    {
        $client = $this->mockClient([
            $this->jsonResponse(['err' => 'Rate limit reached'], 429, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => '1700000000',
                'Retry-After' => '7',
            ]),
        ]);

        try {
            $client->get('task/1');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(7, $e->retryAfter());
            $this->assertSame(100, $e->limit());
            $this->assertSame(0, $e->remaining());
            $this->assertSame(1700000000, $e->resetsAt());
        }
    }

    public function test_it_retries_a_throttled_request_and_then_succeeds(): void
    {
        $client = $this->mockClient([
            $this->jsonResponse(['err' => 'Rate limit reached'], 429, ['Retry-After' => '0']),
            $this->jsonResponse(['id' => 'abc']),
        ], retries: 1);

        $this->assertSame(['id' => 'abc'], $client->get('task/abc'));
        $this->assertSame(2, $this->requestCount());
    }

    public function test_it_stops_retrying_once_the_budget_is_spent(): void
    {
        $client = $this->mockClient([
            $this->jsonResponse(['err' => 'Server error'], 500),
            $this->jsonResponse(['err' => 'Server error'], 500),
        ], retries: 1);

        $this->expectException(ServerException::class);

        try {
            $client->get('task/1');
        } finally {
            $this->assertSame(2, $this->requestCount());
        }
    }

    public function test_it_wraps_connection_failures(): void
    {
        $client = $this->mockClient([
            new \GuzzleHttp\Exception\ConnectException('DNS failure', new Request('GET', 'task/1')),
        ]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/Could not reach the ClickUp API/');

        $client->get('task/1');
    }

    public function test_it_reports_rate_limit_state_from_the_last_response(): void
    {
        $client = $this->mockClient([
            $this->jsonResponse([], 200, [
                'X-RateLimit-Limit' => '100',
                'X-RateLimit-Remaining' => '96',
                'X-RateLimit-Reset' => '1700000060',
            ]),
        ]);

        $client->get('user');

        $this->assertSame(
            ['limit' => 100, 'remaining' => 96, 'reset' => 1700000060],
            $client->rateLimit()
        );
    }

    public function test_it_normalises_a_custom_base_url(): void
    {
        $client = new Client('pk_test', baseUrl: 'https://example.test/api/v2');

        $this->assertSame('https://example.test/api/v2/', $client->baseUrl());
    }
}
