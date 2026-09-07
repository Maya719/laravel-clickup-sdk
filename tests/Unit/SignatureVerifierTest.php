<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Tests\Unit;

use Maya719\ClickUp\Webhooks\SignatureVerifier;
use PHPUnit\Framework\TestCase;

class SignatureVerifierTest extends TestCase
{
    public function test_it_accepts_a_signature_it_produced(): void
    {
        $verifier = new SignatureVerifier('shhh');
        $payload = '{"event":"taskCreated","task_id":"abc"}';

        $this->assertTrue($verifier->verify($payload, $verifier->sign($payload)));
    }

    public function test_it_matches_the_hmac_sha256_hex_digest_clickup_sends(): void
    {
        $payload = '{"event":"taskCreated"}';

        $this->assertSame(
            hash_hmac('sha256', $payload, 'shhh'),
            (new SignatureVerifier('shhh'))->sign($payload)
        );
    }

    public function test_it_rejects_a_tampered_payload(): void
    {
        $verifier = new SignatureVerifier('shhh');
        $signature = $verifier->sign('{"event":"taskCreated"}');

        $this->assertFalse($verifier->verify('{"event":"taskDeleted"}', $signature));
    }

    public function test_it_rejects_a_signature_from_another_secret(): void
    {
        $payload = '{"event":"taskCreated"}';

        $this->assertFalse(
            (new SignatureVerifier('shhh'))->verify($payload, (new SignatureVerifier('other'))->sign($payload))
        );
    }

    public function test_it_rejects_missing_signatures_and_an_unset_secret(): void
    {
        $verifier = new SignatureVerifier('shhh');

        $this->assertFalse($verifier->verify('{}', null));
        $this->assertFalse($verifier->verify('{}', ''));

        // With no secret configured nothing can be trusted, not even a "correct" digest.
        $unconfigured = new SignatureVerifier('');
        $this->assertFalse($unconfigured->verify('{}', hash_hmac('sha256', '{}', '')));
    }
}
