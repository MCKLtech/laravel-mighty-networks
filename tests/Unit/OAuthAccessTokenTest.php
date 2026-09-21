<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;
use PHPUnit\Framework\TestCase;

final class OAuthAccessTokenTest extends TestCase
{
    public function test_it_builds_an_absolute_expiry_from_expires_in(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2024-01-01T00:00:00+00:00'));

        $token = AccessToken::fromArray([
            'access_token' => 'abc',
            'refresh_token' => 'def',
            'expires_in' => 3600,
            'scope' => 'read:userinfo read:network',
            'token_type' => 'Bearer',
        ]);

        $this->assertSame('abc', $token->accessToken);
        $this->assertSame('def', $token->refreshToken);
        $this->assertSame('2024-01-01T01:00:00+00:00', $token->expiresAt?->toIso8601String());
        $this->assertSame(['read:userinfo', 'read:network'], $token->scopes);
        $this->assertFalse($token->isExpired());

        CarbonImmutable::setTestNow();
    }

    public function test_it_round_trips_through_an_array(): void
    {
        $original = new AccessToken(
            accessToken: 'abc',
            refreshToken: 'def',
            expiresAt: CarbonImmutable::parse('2030-01-01T00:00:00+00:00'),
            scopes: ['read:network'],
            tokenType: 'Bearer',
        );

        $restored = AccessToken::fromArray($original->toArray());

        $this->assertSame($original->accessToken, $restored->accessToken);
        $this->assertSame($original->refreshToken, $restored->refreshToken);
        $this->assertSame(
            $original->expiresAt?->toIso8601String(),
            $restored->expiresAt?->toIso8601String(),
        );
        $this->assertSame($original->scopes, $restored->scopes);
    }

    public function test_it_treats_a_token_within_the_leeway_as_expired(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2024-01-01T00:00:00+00:00'));

        $token = new AccessToken(
            accessToken: 'abc',
            expiresAt: CarbonImmutable::now()->addSeconds(30),
        );

        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isExpired(leeway: 0));

        CarbonImmutable::setTestNow();
    }

    public function test_a_token_without_expiry_is_not_expired_and_reports_refreshability(): void
    {
        $token = new AccessToken(accessToken: 'abc', refreshToken: 'def');

        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isRefreshable());
        $this->assertFalse((new AccessToken('abc'))->isRefreshable());
    }
}
