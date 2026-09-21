<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use MCKLtech\MightyNetworks\Auth\OAuthConnector;
use MCKLtech\MightyNetworks\Auth\Requests\ExchangeAuthorizationCodeRequest;
use MCKLtech\MightyNetworks\Auth\Requests\RefreshAccessTokenRequest;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Support\CacheTokenStore;
use MCKLtech\MightyNetworks\Support\OAuthClient;
use MCKLtech\MightyNetworks\Support\Pkce;
use MCKLtech\MightyNetworks\Tests\Support\TestGraphQLRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class OAuthFlowTest extends TestCase
{
    private function oauthClient(MockClient $mock, ?string $clientSecret = 'test-client-secret'): OAuthClient
    {
        $connector = (new OAuthConnector('test-network'))->withMockClient($mock);

        return new OAuthClient(
            connector: $connector,
            clientId: 'test-client-id',
            clientSecret: $clientSecret,
            redirectUri: 'https://example.com/callback',
            scopes: ['read:network'],
        );
    }

    private function store(): CacheTokenStore
    {
        return new CacheTokenStore(new Repository(new ArrayStore), 'test.oauth.');
    }

    public function test_it_builds_an_authorization_url_with_pkce_and_state(): void
    {
        $url = $this->oauthClient(new MockClient)->authorizationUrl(
            state: 'opaque-state',
            codeChallenge: Pkce::challenge('verifier'),
            scopes: ['read:userinfo', 'read:network'],
        );

        $this->assertStringStartsWith('https://test-network.mn.co/oauth/authorize?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('code', $query['response_type']);
        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame('https://example.com/callback', $query['redirect_uri']);
        $this->assertSame('opaque-state', $query['state']);
        $this->assertSame('read:userinfo read:network', $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['code_challenge']);
    }

    public function test_it_exchanges_an_authorization_code_for_a_token(): void
    {
        $mock = new MockClient([MockResponse::make([
            'access_token' => 'access-1',
            'refresh_token' => 'refresh-1',
            'expires_in' => 3600,
            'scope' => 'read:network',
            'token_type' => 'Bearer',
        ], 200)]);

        $token = $this->oauthClient($mock)->exchangeCode('auth-code', 'verifier');

        $this->assertSame('access-1', $token->accessToken);
        $this->assertSame('refresh-1', $token->refreshToken);
        $this->assertSame(['read:network'], $token->scopes);
        $this->assertNotNull($token->expiresAt);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof ExchangeAuthorizationCodeRequest) {
                return false;
            }

            $body = $request->body()->all();

            return $request->resolveEndpoint() === 'oauth/token'
                && $body['grant_type'] === 'authorization_code'
                && $body['code'] === 'auth-code'
                && $body['code_verifier'] === 'verifier'
                && $body['client_secret'] === 'test-client-secret';
        });
    }

    public function test_it_refreshes_a_token(): void
    {
        $mock = new MockClient([MockResponse::make([
            'access_token' => 'access-2',
            'refresh_token' => 'refresh-2',
            'expires_in' => 3600,
        ], 200)]);

        $token = $this->oauthClient($mock)->refresh(new AccessToken(
            accessToken: 'access-1',
            refreshToken: 'refresh-1',
        ));

        $this->assertSame('access-2', $token->accessToken);
        $this->assertSame('refresh-2', $token->refreshToken);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof RefreshAccessTokenRequest) {
                return false;
            }

            $body = $request->body()->all();

            return $body['grant_type'] === 'refresh_token'
                && $body['refresh_token'] === 'refresh-1';
        });
    }

    public function test_it_requires_a_refresh_token_to_refresh(): void
    {
        $this->expectException(MightyNetworksException::class);

        $this->oauthClient(new MockClient)->refresh(new AccessToken(accessToken: 'access-1'));
    }

    public function test_a_rejected_token_request_throws_an_authentication_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'invalid_grant'], 400)]);

        $this->expectException(AuthenticationException::class);

        $this->oauthClient($mock)->refresh(new AccessToken('access-1', 'refresh-1'));
    }

    public function test_the_authenticator_attaches_a_stored_unexpired_token(): void
    {
        $store = $this->store();
        $store->put('default', new AccessToken(
            accessToken: 'stored-token',
            refreshToken: 'refresh-1',
            expiresAt: CarbonImmutable::now()->addHour(),
        ));

        $oauthMock = new MockClient;
        $oauth = $this->oauthClient($oauthMock);
        $graphqlMock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $connector = new GraphQLConnector(
            accessToken: '',
            userAgent: 'test-agent',
            oauth: $oauth,
            tokenStore: $store,
            oauthKey: 'default',
        );

        $connector->withMockClient($graphqlMock)->send(new TestGraphQLRequest);

        $oauthMock->assertNothingSent();

        $graphqlMock->assertSent(function ($request, $response): bool {
            return $response->getPendingRequest()->headers()->get('Authorization') === 'Bearer stored-token';
        });
    }

    public function test_the_authenticator_refreshes_an_expired_token_and_persists_the_rotation(): void
    {
        $store = $this->store();
        $store->put('default', new AccessToken(
            accessToken: 'expired-token',
            refreshToken: 'refresh-1',
            expiresAt: CarbonImmutable::now()->subMinute(),
        ));

        $oauthMock = new MockClient([MockResponse::make([
            'access_token' => 'fresh-token',
            'refresh_token' => 'refresh-2',
            'expires_in' => 3600,
        ], 200)]);

        $oauth = $this->oauthClient($oauthMock);
        $graphqlMock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $connector = new GraphQLConnector(
            accessToken: '',
            userAgent: 'test-agent',
            oauth: $oauth,
            tokenStore: $store,
            oauthKey: 'default',
        );

        $connector->withMockClient($graphqlMock)->send(new TestGraphQLRequest);

        $graphqlMock->assertSent(function ($request, $response): bool {
            return $response->getPendingRequest()->headers()->get('Authorization') === 'Bearer fresh-token';
        });

        $stored = $store->get('default');

        $this->assertInstanceOf(AccessToken::class, $stored);
        $this->assertSame('refresh-2', $stored->refreshToken);
        $this->assertSame('fresh-token', $stored->accessToken);
    }

    public function test_a_failed_refresh_surfaces_as_authentication_required(): void
    {
        $store = $this->store();
        $store->put('default', new AccessToken(
            accessToken: 'expired-token',
            refreshToken: 'refresh-1',
            expiresAt: CarbonImmutable::now()->subMinute(),
        ));

        $oauthMock = new MockClient([MockResponse::make(['error' => 'invalid_grant'], 400)]);

        $connector = new GraphQLConnector(
            accessToken: '',
            userAgent: 'test-agent',
            oauth: $this->oauthClient($oauthMock),
            tokenStore: $store,
            oauthKey: 'default',
        );

        $connector->withMockClient(new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]));

        $this->expectException(AuthenticationException::class);

        $connector->send(new TestGraphQLRequest);
    }

    public function test_the_authenticator_requires_a_stored_token(): void
    {
        $connector = new GraphQLConnector(
            accessToken: '',
            userAgent: 'test-agent',
            oauth: $this->oauthClient(new MockClient),
            tokenStore: $this->store(),
            oauthKey: 'default',
        );

        $connector->withMockClient(new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]));

        $this->expectException(MightyNetworksException::class);

        $connector->send(new TestGraphQLRequest);
    }
}
