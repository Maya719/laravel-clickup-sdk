<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for every error raised by the ClickUp SDK.
 */
class ClickUpException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context  Decoded error payload returned by ClickUp.
     */
    public function __construct(
        string $message,
        protected readonly int $status = 0,
        protected readonly ?string $errorCode = null,
        protected readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * HTTP status code returned by the API (0 when the request never completed).
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * ClickUp's machine readable error code, e.g. "OAUTH_027".
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * The full decoded error payload.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
