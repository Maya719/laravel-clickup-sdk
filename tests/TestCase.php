<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests;

use Maya719\ClickUp\ClickUpServiceProvider;
use Maya719\ClickUp\Facades\ClickUp;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ClickUpServiceProvider::class];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['ClickUp' => ClickUp::class];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('clickup.api_key', 'pk_test_key');
        $app['config']->set('clickup.list_id', '901');
        $app['config']->set('clickup.webhooks.secret', 'webhook-secret');
    }
}
