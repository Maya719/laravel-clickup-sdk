<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

use Throwable;

/**
 * Thrown when the ClickUp rate limit is exhausted (HTTP 429).
 *
 * ClickUp allows 100 requests per minute per token on the Free/Unlimited plans
 * and higher ceilings on paid plans. The limit resets on a rolling minute.
 */
class RateLimitException extends ClickUpException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        int $status = 429,
        ?string $errorCode = null,
        array $context = [],
        private readonly ?int $retryAfter = null,
        private readonly ?int $limit = null,
        private readonly ?int $remaining = null,
        private readonly ?int $resetsAt = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $errorCode, $context, $previous);
    }

    /**
     * Seconds to wait before retrying, when ClickUp advertised a window.
     */
    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Total requests allowed in the current window.
     */
    public function limit(): ?int
    {
        return $this->limit;
    }

    /**
     * Requests left in the current window.
     */
    public function remaining(): ?int
    {
        return $this->remaining;
    }

    /**
     * Unix timestamp at which the current window resets.
     */
    public function resetsAt(): ?int
    {
        return $this->resetsAt;
    }
}
