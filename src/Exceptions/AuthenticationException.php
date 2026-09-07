<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

/**
 * Thrown when ClickUp rejects the supplied token (HTTP 401).
 */
class AuthenticationException extends ClickUpException
{
}
