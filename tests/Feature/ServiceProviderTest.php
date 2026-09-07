<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Feature;

use Maya719\ClickUp\ClickUp;
use Maya719\ClickUp\Facades\ClickUp as ClickUpFacade;
use Maya719\ClickUp\Http\Client;
use Maya719\ClickUp\Tests\TestCase;
use Maya719\ClickUp\Webhooks\SignatureVerifier;

class ServiceProviderTest extends TestCase
{
    public function test_it_resolves_the_client_from_the_container(): void
    {
        $this->assertInstanceOf(ClickUp::class, $this->app->make('clickup'));
        $this->assertInstanceOf(ClickUp::class, $this->app->make(ClickUp::class));
    }

    public function test_the_binding_is_a_singleton_shared_with_the_alias(): void
    {
        $this->assertSame($this->app->make('clickup'), $this->app->make(ClickUp::class));
        $this->assertSame($this->app->make(Client::class), $this->app->make(ClickUp::class)->http());
    }

    public function test_it_applies_configuration_to_the_transport(): void
    {
        $clickup = $this->app->make(ClickUp::class);

        $this->assertSame('https://api.clickup.com/api/v2/', $clickup->http()->baseUrl());
        $this->assertSame('901', $clickup->defaultListId());
        $this->assertFalse($clickup->http()->isOAuth());
    }

    public function test_the_facade_proxies_to_the_bound_instance(): void
    {
        $this->assertInstanceOf(\Maya719\ClickUp\Resources\Tasks::class, ClickUpFacade::tasks());
        $this->assertSame('901', ClickUpFacade::defaultListId());
    }

    public function test_it_reads_the_webhook_secret_from_config(): void
    {
        $verifier = $this->app->make(SignatureVerifier::class);

        $this->assertTrue($verifier->verify('{}', hash_hmac('sha256', '{}', 'webhook-secret')));
    }

    public function test_it_still_honours_the_legacy_webhook_secret_key(): void
    {
        $this->app['config']->set('clickup.webhooks.secret', null);
        $this->app['config']->set('clickup.webhook_secret', 'legacy-secret');
        $this->app->forgetInstance(SignatureVerifier::class);

        $verifier = $this->app->make(SignatureVerifier::class);

        $this->assertTrue($verifier->verify('{}', hash_hmac('sha256', '{}', 'legacy-secret')));
    }

    public function test_the_config_file_is_publishable(): void
    {
        $this->assertArrayHasKey(
            'clickup-config',
            \Illuminate\Support\ServiceProvider::$publishGroups
        );
    }

    public function test_the_webhook_route_is_not_registered_by_default(): void
    {
        $this->assertNull($this->app['router']->getRoutes()->getByName('clickup.webhook'));
    }
}
