<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;
use MCKLtech\MightyNetworks\Support\CacheTokenStore;
use PHPUnit\Framework\TestCase;

final class CacheTokenStoreTest extends TestCase
{
    private function store(?int $ttl = null): CacheTokenStore
    {
        return new CacheTokenStore(new Repository(new ArrayStore), 'test.oauth.', $ttl);
    }

    public function test_it_puts_and_gets_a_token(): void
    {
        $store = $this->store();

        $token = new AccessToken(
            accessToken: 'abc',
            refreshToken: 'def',
            expiresAt: CarbonImmutable::parse('2030-01-01T00:00:00+00:00'),
            scopes: ['read:network'],
        );

        $store->put('default', $token);

        $restored = $store->get('default');

        $this->assertInstanceOf(AccessToken::class, $restored);
        $this->assertSame('abc', $restored->accessToken);
        $this->assertSame('def', $restored->refreshToken);
        $this->assertSame(['read:network'], $restored->scopes);
    }

    public function test_it_overwrites_a_rotated_refresh_token(): void
    {
        $store = $this->store();

        $store->put('default', new AccessToken(accessToken: 'a1', refreshToken: 'r1'));
        $store->put('default', new AccessToken(accessToken: 'a2', refreshToken: 'r2'));

        $this->assertSame('r2', $store->get('default')?->refreshToken);
    }

    public function test_it_returns_null_when_nothing_is_stored(): void
    {
        $this->assertNull($this->store()->get('missing'));
    }

    public function test_it_forgets_a_token(): void
    {
        $store = $this->store();

        $store->put('default', new AccessToken(accessToken: 'abc'));
        $store->forget('default');

        $this->assertNull($store->get('default'));
    }

    public function test_it_stores_forever_when_no_ttl_is_configured(): void
    {
        $cache = $this->createMock(CacheRepository::class);

        $cache->expects($this->once())
            ->method('forever')
            ->with('test.oauth.default', $this->isType('array'));

        $cache->expects($this->never())->method('put');

        (new CacheTokenStore($cache, 'test.oauth.'))->put('default', new AccessToken(accessToken: 'abc'));
    }

    public function test_it_stores_with_the_configured_ttl(): void
    {
        $cache = $this->createMock(CacheRepository::class);

        $cache->expects($this->once())
            ->method('put')
            ->with('test.oauth.default', $this->isType('array'), 60);

        $cache->expects($this->never())->method('forever');

        (new CacheTokenStore($cache, 'test.oauth.', 60))->put('default', new AccessToken(accessToken: 'abc'));
    }

    public function test_get_returns_null_when_the_cached_value_is_not_an_array(): void
    {
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('get')->willReturn('not-an-array');

        $this->assertNull((new CacheTokenStore($cache, 'test.oauth.'))->get('default'));
    }
}
