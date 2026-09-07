<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Maya719\ClickUp\Events\WebhookReceived;
use Maya719\ClickUp\Tests\TestCase;

class WebhookRouteTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('clickup.webhooks.route.enabled', true);
    }

    public function test_it_registers_the_receiver_route(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('clickup.webhook');

        $this->assertNotNull($route);
        $this->assertSame('clickup/webhook', $route->uri());
        $this->assertContains('POST', $route->methods());
    }

    public function test_it_dispatches_an_event_for_a_correctly_signed_delivery(): void
    {
        Event::fake([WebhookReceived::class]);

        $payload = ['event' => 'taskStatusUpdated', 'task_id' => 'abc123', 'webhook_id' => 'wh_1'];

        $this->postJson(
            '/clickup/webhook',
            $payload,
            ['X-Signature' => $this->sign($payload)]
        )->assertOk();

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event): bool {
            return $event->event === 'taskStatusUpdated'
                && $event->taskId() === 'abc123'
                && $event->webhookId() === 'wh_1';
        });
    }

    public function test_it_rejects_a_delivery_with_a_bad_signature(): void
    {
        Event::fake([WebhookReceived::class]);

        $this->postJson(
            '/clickup/webhook',
            ['event' => 'taskCreated'],
            ['X-Signature' => 'not-the-right-digest']
        )->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_it_rejects_a_delivery_with_no_signature_header(): void
    {
        Event::fake([WebhookReceived::class]);

        $this->postJson('/clickup/webhook', ['event' => 'taskCreated'])->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_it_rejects_a_signed_body_that_is_missing_the_event_name(): void
    {
        Event::fake([WebhookReceived::class]);

        $payload = ['task_id' => 'abc123'];

        $this->postJson('/clickup/webhook', $payload, ['X-Signature' => $this->sign($payload)])
            ->assertStatus(422);

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_the_event_exposes_history_items(): void
    {
        $event = new WebhookReceived('taskUpdated', [
            'task_id' => 'abc',
            'history_items' => [['field' => 'status']],
        ]);

        $this->assertSame([['field' => 'status']], $event->historyItems());
        $this->assertSame('abc', $event->taskId());
        $this->assertNull($event->webhookId());
    }

    /**
     * Sign a payload the way ClickUp does: HMAC-SHA256 over the exact raw body.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sign(array $payload): string
    {
        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR),
            'webhook-secret'
        );
    }
}
