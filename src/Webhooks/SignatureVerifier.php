<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Webhooks;

/**
 * Verifies the `X-Signature` header ClickUp sends with every webhook delivery.
 *
 * ClickUp signs the exact raw request body with HMAC-SHA256 using the secret
 * returned when the webhook was created, and sends the digest hex-encoded.
 * Verify against the raw body — re-encoding a decoded payload changes the bytes
 * and the signature will not match.
 *
 * @see https://developer.clickup.com/docs/webhooksignature
 */
class SignatureVerifier
{
    public const HEADER = 'X-Signature';

    public function __construct(protected readonly string $secret)
    {
    }

    /**
     * Compute the expected signature for a raw request body.
     */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * Whether $signature is a valid signature for $payload.
     */
    public function verify(string $payload, ?string $signature): bool
    {
        if ($this->secret === '' || $signature === null || $signature === '') {
            return false;
        }

        return hash_equals($this->sign($payload), trim($signature));
    }
}
