<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Support\Pkce;
use PHPUnit\Framework\TestCase;

final class PkceTest extends TestCase
{
    public function test_a_verifier_is_url_safe_and_long_enough(): void
    {
        $verifier = Pkce::verifier();

        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $verifier);
    }

    public function test_a_challenge_is_a_deterministic_s256_hash(): void
    {
        $verifier = 'fixed-verifier-value';

        $challenge = Pkce::challenge($verifier);

        $this->assertSame($challenge, Pkce::challenge($verifier));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $challenge);
        $this->assertStringNotContainsString('=', $challenge);
        $this->assertSame(Pkce::base64Url(hash('sha256', $verifier, true)), $challenge);
    }
}
