<?php

declare(strict_types=1);

namespace Maya719\ClickUp;

use Illuminate\Support\ServiceProvider;
use Maya719\ClickUp\Http\Client;
use Maya719\ClickUp\Webhooks\SignatureVerifier;

class ClickUpServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     */
    public function register(): void
    {
        // Merge package configuration with app configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/clickup.php',
            'clickup'
        );

        $this->app->singleton(Client::class, function ($app): Client {
            $config = $app['config']['clickup'] ?? [];

            return new Client(
                token: (string) ($config['api_key'] ?? ''),
                isOAuth: (bool) ($config['is_oauth'] ?? false),
                baseUrl: (string) ($config['base_url'] ?? Client::DEFAULT_BASE_URL),
                retries: (int) ($config['retries'] ?? 2),
                timeout: (float) ($config['timeout'] ?? 30),
            );
        });

        // Bind the main ClickUp instance as a singleton.
        $this->app->singleton(ClickUp::class, function ($app): ClickUp {
            $config = $app['config']['clickup'] ?? [];
            $listId = $config['list_id'] ?? null;

            return ClickUp::fromClient(
                $app->make(Client::class),
                $listId === null ? null : (string) $listId,
            );
        });

        // The facade resolves through this alias.
        $this->app->alias(ClickUp::class, 'clickup');

        $this->app->singleton(SignatureVerifier::class, function ($app): SignatureVerifier {
            $config = $app['config']['clickup'] ?? [];

            // `webhook_secret` is the pre-1.0 key, still honoured as a fallback.
            $secret = $config['webhooks']['secret'] ?? $config['webhook_secret'] ?? '';

            return new SignatureVerifier((string) $secret);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // php artisan vendor:publish --tag=clickup-config
            $this->publishes([
                __DIR__ . '/../config/clickup.php' => config_path('clickup.php'),
            ], 'clickup-config');
        }

        if ((bool) ($this->app['config']['clickup.webhooks.route.enabled'] ?? false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/clickup.php');
        }
    }
}
