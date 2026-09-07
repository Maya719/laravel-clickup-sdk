<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Unit;

use Maya719\ClickUp\Tests\MocksClickUp;
use PHPUnit\Framework\TestCase;

class QueryBuildingTest extends TestCase
{
    use MocksClickUp;

    public function test_it_does_not_double_bracket_a_key_that_already_has_brackets(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/1/task', ['statuses[]' => ['open', 'in progress']]);

        $query = urldecode($this->lastRequest()->getUri()->getQuery());
        $this->assertSame('statuses[]=open&statuses[]=in progress', $query);
    }

    public function test_it_keeps_bracket_notation_for_associative_values(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/1/task', ['custom_fields' => ['field_id' => 'abc']]);

        $this->assertSame(
            'custom_fields[field_id]=abc',
            urldecode($this->lastRequest()->getUri()->getQuery())
        );
    }

    public function test_it_encodes_booleans_inside_lists(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('list/1/task', ['flags' => [true, false]]);

        $this->assertSame(
            'flags[]=true&flags[]=false',
            urldecode($this->lastRequest()->getUri()->getQuery())
        );
    }

    public function test_it_url_encodes_query_values(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('team/1/task', ['search' => 'a&b=c']);

        $this->assertSame('search=a%26b%3Dc', $this->lastRequest()->getUri()->getQuery());
    }

    public function test_bulk_time_in_status_repeats_plain_task_id_keys(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse([])]);

        $clickup->tasks()->bulkTimeInStatus(['abc', 'def']);

        $this->assertSame(
            'task_ids=abc&task_ids=def',
            urldecode($this->lastRequest()->getUri()->getQuery())
        );
    }

    public function test_a_request_with_no_query_has_an_empty_query_string(): void
    {
        $client = $this->mockClient([$this->jsonResponse([])]);

        $client->get('user', []);

        $this->assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function test_workspace_task_filters_use_bracketed_arrays(): void
    {
        $clickup = $this->mockClickUp([$this->jsonResponse(['tasks' => []])]);

        $clickup->tasks()->filterByWorkspace('team-1', [
            'space_ids' => ['1', '2'],
            'include_closed' => true,
        ]);

        $query = urldecode($this->lastRequest()->getUri()->getQuery());
        $this->assertStringContainsString('space_ids[]=1', $query);
        $this->assertStringContainsString('space_ids[]=2', $query);
        $this->assertStringContainsString('include_closed=true', $query);
    }
}
