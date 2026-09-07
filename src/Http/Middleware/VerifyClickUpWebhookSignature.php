<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Maya719\ClickUp\Webhooks\SignatureVerifier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Rejects webhook deliveries that are not signed with the configured secret.
 */
class VerifyClickUpWebhookSignature
{
    public function __construct(protected readonly SignatureVerifier $verifier)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // getContent() returns the raw body, which is what ClickUp signed.
        if (! $this->verifier->verify($request->getContent(), $request->header(SignatureVerifier::HEADER))) {
            throw new AccessDeniedHttpException('Invalid ClickUp webhook signature.');
        }

        return $next($request);
    }
}
