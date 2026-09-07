<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

use Maya719\ClickUp\Http\Client;

/**
 * Base class for every ClickUp API resource group.
 */
abstract class Resource
{
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * Escape a path segment so ids and names containing "/" or spaces stay intact.
     */
    protected function segment(string|int $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * Pull a list out of an envelope such as {"tasks": [...]} without tripping on
     * endpoints that answer with a bare array.
     *
     * @param  array<mixed>  $response
     * @return array<int, mixed>
     */
    protected function unwrap(array $response, string $key): array
    {
        $items = $response[$key] ?? $response;

        return is_array($items) ? array_values($items) : [];
    }
}
