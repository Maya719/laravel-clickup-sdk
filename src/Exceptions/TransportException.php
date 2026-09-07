<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

/**
 * Thrown when the request never reached ClickUp (DNS, TLS, timeout).
 */
class TransportException extends ClickUpException
{
}
