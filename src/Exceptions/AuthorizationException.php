<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

/**
 * Thrown when the token is valid but lacks access to the resource (HTTP 403).
 */
class AuthorizationException extends ClickUpException
{
}
