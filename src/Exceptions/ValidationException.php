<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Exceptions;

/**
 * Thrown when ClickUp rejects the request payload (HTTP 400 / 422).
 */
class ValidationException extends ClickUpException
{
}
