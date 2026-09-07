<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Unit;

use Maya719\ClickUp\Exceptions\ClickUpException;
use Maya719\ClickUp\Tests\MocksClickUp;
use PHPUnit\Framework\TestCase;

class ResourcesTest extends TestCase
{
    use MocksClickUp;

    public function test_it_lists_tasks_and_unwraps_the_envelope(): void
    {
        $clickup = $this->mockClickUp([
            $this->jsonResponse(['tasks' => [['id' => 'a'], ['id' => 'b']], 'last_page' => true]),
        ]);

        $tasks = $clickup->tasks()->all('901', ['archived' => false]);

        $this->assertSame([['id' => 'a'], ['id' => 'b']], $tasks);
        $this->assertSame('GET', $this->lastRequest()->getMethod());
        $this->assertSame('/api/v2/list/901/task', $this->lastRequest()->getUri()->getPath());
    }

    public function test_it_creates_a_task_with_a_json_body(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['id' => 'new'])]);

        $task = $clickup->tasks()->create('901', ['name' => 'Ship it', 'priority' => 2]);

        $this->assertSame(['id' => 'new'], $task);
        $this->assertSame('POST', $this->lastRequest()->getMethod());
        $this->assertSame(['name' => 'Ship it', 'priority' => 2], $this->lastRequestBody());
    }

    public function test_the_task_cursor_walks_every_page(): void
    {
        $clickup = $this->mockClickUp([
            $this->jsonResponse(['tasks' => [['id' => '1'], ['id' => '2']], 'last_page' => false]),
            $this->jsonResponse(['tasks' => [['id' => '3']], 'last_page' => true]),
        ]);

        $ids = [];

        foreach ($clickup->tasks()->cursor('901') as $task) {
            $ids[] = $task['id'];
        }

        $this->assertSame(['1', '2', '3'], $ids);
        $this->assertSame(2, $this->requestCount());
        $this->assertStringContainsString('page=0', $this->requestAt(0)->getUri()->getQuery());
        $this->assertStringContainsString('page=1', $this->requestAt(1)->getUri()->getQuery());
    }

    public function test_the_cursor_stops_on_an_empty_page_without_the_last_page_flag(): void
    {
        $clickup = $this->mockClickUp([
            $this->jsonResponse(['tasks' => [['id' => '1']]]),
            $this->jsonResponse(['tasks' => []]),
        ]);

        $this->assertCount(1, iterator_to_array($clickup->tasks()->cursor('901'), false));
        $this->assertSame(2, $this->requestCount());
    }

    public function test_it_escapes_ids_used_as_path_segments(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse([])]);

        $clickup->tags()->addToTask('abc123', 'needs review / triage');

        $this->assertSame(
            '/api/v2/task/abc123/tag/needs%20review%20%2F%20triage',
            $this->lastRequest()->getUri()->getPath()
        );
    }

    public function test_it_wraps_a_custom_field_value(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse([])]);

        $clickup->customFields()->set('abc123', 'field-uuid', 'hello');

        $this->assertSame(['value' => 'hello'], $this->lastRequestBody());
        $this->assertSame('/api/v2/task/abc123/field/field-uuid', $this->lastRequest()->getUri()->getPath());
    }

    public function test_it_creates_a_webhook_with_a_wildcard_event_by_default(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['id' => 'wh_1'])]);

        $clickup->webhooks()->create('team-1', 'https://example.test/hook');

        $this->assertSame(
            ['endpoint' => 'https://example.test/hook', 'events' => ['*']],
            $this->lastRequestBody()
        );
    }

    public function test_it_removes_a_dependency_using_query_parameters(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse([])]);

        $clickup->dependencies()->remove('task-1', dependsOn: 'task-2');

        $this->assertSame('DELETE', $this->lastRequest()->getMethod());
        $this->assertSame('depends_on=task-2', $this->lastRequest()->getUri()->getQuery());
    }

    public function test_it_unwraps_the_authorized_user(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['user' => ['id' => 7, 'username' => 'ada']])]);

        $this->assertSame(['id' => 7, 'username' => 'ada'], $clickup->authorizedUser()->get());
    }

    public function test_it_uploads_an_attachment_as_multipart(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['id' => 'att_1'])]);

        $clickup->attachments()->uploadContents('task-1', 'file body', 'notes.txt');

        $request = $this->lastRequest();
        $this->assertStringStartsWith('multipart/form-data', $request->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('filename="notes.txt"', (string) $request->getBody());
    }

    public function test_uploading_a_missing_file_fails_before_any_request(): void
    {
        $clickup = $this->mockClickUp([]);

        $this->expectException(ClickUpException::class);
        $this->expectExceptionMessageMatches('/does not exist or is not readable/');

        $clickup->attachments()->upload('task-1', __DIR__ . '/definitely-missing.txt');
    }

    public function test_it_falls_back_to_the_default_list(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['id' => 'x'])], defaultListId: '555');

        $clickup->createTask(['name' => 'From default list']);

        $this->assertSame('/api/v2/list/555/task', $this->lastRequest()->getUri()->getPath());
    }

    public function test_it_explains_when_no_list_is_available(): void
    {
        $clickup = $this->mockClickUp([]);

        $this->expectException(ClickUpException::class);
        $this->expectExceptionMessageMatches('/CLICKUP_LIST_ID/');

        $clickup->createTask(['name' => 'Nowhere to go']);
    }

    public function test_resources_are_memoised(): void
    {
        $clickup = $this->mockClickUp([]);

        $this->assertSame($clickup->tasks(), $clickup->tasks());
        $this->assertSame($clickup->workspaces(), $clickup->teams());
    }

    public function test_the_oauth_helper_builds_an_authorization_url(): void
    {
        $oauth = $this->mockClickUp([])->oauth('client-id', 'secret');

        $url = $oauth->authorizationUrl('https://example.test/callback', 'state-123');

        $this->assertStringStartsWith('https://app.clickup.com/api?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.test%2Fcallback', $url);
        $this->assertStringContainsString('state=state-123', $url);
    }

    public function test_it_exchanges_an_oauth_code_for_a_token(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['access_token' => 'tok_abc'])]);

        $token = $clickup->oauth('client-id', 'secret')->accessTokenString('code-123');

        $this->assertSame('tok_abc', $token);

        $query = $this->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('client_id=client-id', $query);
        $this->assertStringContainsString('client_secret=secret', $query);
        $this->assertStringContainsString('code=code-123', $query);
    }

    public function test_a_missing_access_token_raises_a_clear_error(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['err' => 'Bad code'])]);

        $this->expectException(ClickUpException::class);
        $this->expectExceptionMessageMatches('/did not return an access_token/');

        $clickup->oauth('client-id', 'secret')->accessTokenString('bad');
    }
}
