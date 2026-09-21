<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks;

use Illuminate\Contracts\Cache\Repository;
use MCKLtech\MightyNetworks\Auth\OAuthConnector;
use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Connectors\ApiConnector;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\Contracts\TokenStore;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\Resources\AbuseReportsResource;
use MCKLtech\MightyNetworks\Resources\AssetsResource;
use MCKLtech\MightyNetworks\Resources\BadgesResource;
use MCKLtech\MightyNetworks\Resources\CollectionsResource;
use MCKLtech\MightyNetworks\Resources\CommentsResource;
use MCKLtech\MightyNetworks\Resources\CustomFieldsResource;
use MCKLtech\MightyNetworks\Resources\EventsResource;
use MCKLtech\MightyNetworks\Resources\InvitesResource;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Resources\NetworkResource;
use MCKLtech\MightyNetworks\Resources\PlansResource;
use MCKLtech\MightyNetworks\Resources\PollsResource;
use MCKLtech\MightyNetworks\Resources\PostsResource;
use MCKLtech\MightyNetworks\Resources\PurchasesResource;
use MCKLtech\MightyNetworks\Resources\SpacesResource;
use MCKLtech\MightyNetworks\Resources\SubscriptionsResource;
use MCKLtech\MightyNetworks\Resources\TagsResource;
use MCKLtech\MightyNetworks\Support\CacheTokenStore;
use MCKLtech\MightyNetworks\Support\OAuthClient;
use SensitiveParameter;

/**
 * A single configured Mighty Network connection.
 *
 * Connectors are memoised so repeated calls return the same, already-configured
 * instance.
 */
final class MightyNetworks
{
    private ?AdminConnector $adminConnector = null;

    private ?GraphQLConnector $graphqlConnector = null;

    private ?GraphQLClient $graphqlClient = null;

    private ?AbuseReportsResource $abuseReportsResource = null;

    private ?AssetsResource $assetsResource = null;

    private ?BadgesResource $badgesResource = null;

    private ?CollectionsResource $collectionsResource = null;

    private ?CommentsResource $commentsResource = null;

    private ?CustomFieldsResource $customFieldsResource = null;

    private ?EventsResource $eventsResource = null;

    private ?InvitesResource $invitesResource = null;

    private ?MembersResource $membersResource = null;

    private ?NetworkResource $networkResource = null;

    private ?PlansResource $plansResource = null;

    private ?PollsResource $pollsResource = null;

    private ?PostsResource $postsResource = null;

    private ?PurchasesResource $purchasesResource = null;

    private ?SpacesResource $spacesResource = null;

    private ?SubscriptionsResource $subscriptionsResource = null;

    private ?TagsResource $tagsResource = null;

    /**
     * Build an ad-hoc connection from explicit credentials.
     *
     * This is the multi-tenant path: credentials can come from anywhere at
     * runtime — a database column, a job payload, a form request — rather than
     * from the config file. Anything not supplied falls back to the package
     * defaults, overridable with `$overrides`.
     *
     * Passing a null `$adminToken` yields a GraphQL-only connection, so supply
     * an OAuth access token instead:
     *
     * ```php
     * $client = MightyNetworks::make($adminToken, $networkId);
     * $client = MightyNetworks::make(null, $networkId, [
     *     'subdomain' => 'acme',
     *     'oauth' => ['access_token' => $oauthToken],
     * ]);
     * ```
     *
     * @param  array<string, mixed>  $overrides  Any connection config key.
     */
    public static function make(
        #[SensitiveParameter]
        ?string $adminToken,
        int|string $networkId,
        array $overrides = [],
        ?Repository $cache = null,
        string $name = 'runtime',
    ): self {
        $config = array_replace_recursive(self::defaultConfig(), $overrides);

        $config['network_id'] = $networkId;

        if (is_string($adminToken) && $adminToken !== '') {
            $config['admin_token'] = $adminToken;
        }

        return new self($config, $name, $cache);
    }

    /**
     * The package defaults used by {@see self::make()}.
     *
     * @return array<string, mixed>
     */
    private static function defaultConfig(): array
    {
        return [
            'base_url' => 'https://api.mn.co',
            'user_agent' => ApiConnector::DEFAULT_USER_AGENT,
            'retry' => [
                'tries' => 3,
                'interval' => 500,
                'exponential_backoff' => true,
            ],
            'rate_limits' => [
                'enabled' => true,
                'per_minute' => 60,
                'per_day' => null,
                'store' => null,
            ],
            'cache' => [
                'enabled' => false,
                'store' => null,
                'ttl' => 300,
            ],
            'oauth' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $name = 'default',
        private readonly ?Repository $cache = null,
    ) {}

    /**
     * The name of this connection as configured.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * The configured Network ID.
     */
    public function networkId(): int|string
    {
        $id = $this->config['network_id'] ?? null;

        if (is_int($id) || (is_string($id) && $id !== '')) {
            return $id;
        }

        throw new MightyNetworksException(
            sprintf('No network_id configured for the [%s] Mighty Networks connection.', $this->name),
        );
    }

    /**
     * The Admin REST connector for this Network.
     */
    public function admin(): AdminConnector
    {
        return $this->adminConnector ??= new AdminConnector(
            adminToken: $this->adminToken(),
            baseUrl: $this->baseUrl(),
            userAgent: $this->userAgent(),
            retry: $this->arrayConfig('retry'),
            rateLimits: $this->arrayConfig('rate_limits'),
            cache: $this->cache,
        );
    }

    /**
     * The GraphQL connector for this Network.
     */
    public function graphql(): GraphQLConnector
    {
        return $this->graphqlConnector ??= new GraphQLConnector(
            accessToken: $this->staticAccessToken(),
            baseUrl: $this->baseUrl(),
            userAgent: $this->userAgent(),
            retry: $this->arrayConfig('retry'),
            rateLimits: $this->arrayConfig('rate_limits'),
            cache: $this->cache,
            oauth: $this->oauthClient(),
            tokenStore: $this->tokenStore(),
            oauthKey: $this->name,
        );
    }

    /**
     * The ergonomic GraphQL client for this Network.
     */
    public function graphqlClient(): GraphQLClient
    {
        return $this->graphqlClient ??= new GraphQLClient(
            connector: $this->graphql(),
            networkIdOrSubdomain: $this->subdomain() ?? $this->networkId(),
        );
    }

    /**
     * The members resource, scoped to this Network.
     */
    public function members(): MembersResource
    {
        return $this->membersResource ??= new MembersResource($this->admin(), $this->networkId());
    }

    /**
     * The network resource, scoped to this Network.
     */
    public function network(): NetworkResource
    {
        return $this->networkResource ??= new NetworkResource($this->admin(), $this->networkId());
    }

    /**
     * The posts resource, scoped to this Network.
     */
    public function posts(): PostsResource
    {
        return $this->postsResource ??= new PostsResource($this->admin(), $this->networkId());
    }

    /**
     * The comments resource, scoped to this Network.
     */
    public function comments(): CommentsResource
    {
        return $this->commentsResource ??= new CommentsResource($this->admin(), $this->networkId());
    }

    /**
     * The events resource, scoped to this Network.
     */
    public function events(): EventsResource
    {
        return $this->eventsResource ??= new EventsResource($this->admin(), $this->networkId());
    }

    /**
     * The plans resource, scoped to this Network.
     */
    public function plans(): PlansResource
    {
        return $this->plansResource ??= new PlansResource($this->admin(), $this->networkId());
    }

    /**
     * The subscriptions resource, scoped to this Network.
     */
    public function subscriptions(): SubscriptionsResource
    {
        return $this->subscriptionsResource ??= new SubscriptionsResource($this->admin(), $this->networkId());
    }

    /**
     * The purchases resource, scoped to this Network.
     */
    public function purchases(): PurchasesResource
    {
        return $this->purchasesResource ??= new PurchasesResource($this->admin(), $this->networkId());
    }

    /**
     * The invites resource, scoped to this Network.
     */
    public function invites(): InvitesResource
    {
        return $this->invitesResource ??= new InvitesResource($this->admin(), $this->networkId());
    }

    /**
     * The spaces resource, scoped to this Network.
     */
    public function spaces(): SpacesResource
    {
        return $this->spacesResource ??= new SpacesResource($this->admin(), $this->networkId());
    }

    /**
     * The collections resource, scoped to this Network.
     */
    public function collections(): CollectionsResource
    {
        return $this->collectionsResource ??= new CollectionsResource($this->admin(), $this->networkId());
    }

    /**
     * The tags resource, scoped to this Network.
     */
    public function tags(): TagsResource
    {
        return $this->tagsResource ??= new TagsResource($this->admin(), $this->networkId());
    }

    /**
     * The badges resource, scoped to this Network.
     */
    public function badges(): BadgesResource
    {
        return $this->badgesResource ??= new BadgesResource($this->admin(), $this->networkId());
    }

    /**
     * The custom fields resource, scoped to this Network.
     */
    public function customFields(): CustomFieldsResource
    {
        return $this->customFieldsResource ??= new CustomFieldsResource($this->admin(), $this->networkId());
    }

    /**
     * The polls resource, scoped to this Network.
     */
    public function polls(): PollsResource
    {
        return $this->pollsResource ??= new PollsResource($this->admin(), $this->networkId());
    }

    /**
     * The assets resource, scoped to this Network.
     */
    public function assets(): AssetsResource
    {
        return $this->assetsResource ??= new AssetsResource($this->admin(), $this->networkId());
    }

    /**
     * The abuse reports resource, scoped to this Network.
     */
    public function abuseReports(): AbuseReportsResource
    {
        return $this->abuseReportsResource ??= new AbuseReportsResource($this->admin(), $this->networkId());
    }

    /**
     * The configured base URL.
     */
    public function baseUrl(): string
    {
        $baseUrl = $this->config['base_url'] ?? null;

        return is_string($baseUrl) && $baseUrl !== ''
            ? $baseUrl
            : 'https://api.mn.co';
    }

    /**
     * The configured User-Agent (mandatory for api.mn.co).
     */
    public function userAgent(): string
    {
        $userAgent = $this->config['user_agent'] ?? null;

        return is_string($userAgent) && $userAgent !== ''
            ? $userAgent
            : ApiConnector::DEFAULT_USER_AGENT;
    }

    /**
     * The long-lived Admin API key.
     */
    private function adminToken(): string
    {
        $token = $this->config['admin_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new MightyNetworksException(
                sprintf('No admin_token configured for the [%s] Mighty Networks connection.', $this->name),
            );
        }

        return $token;
    }

    /**
     * The Network subdomain (e.g. "acme" for acme.mn.co), when configured.
     *
     * The GraphQL API accepts either the numeric Network ID or the subdomain,
     * and the OAuth endpoints live on the community host, so the subdomain is
     * required for the full OAuth flow.
     */
    public function subdomain(): ?string
    {
        $subdomain = $this->config['subdomain'] ?? null;

        return is_string($subdomain) && $subdomain !== '' ? $subdomain : null;
    }

    /**
     * The static GraphQL access token, when one is configured.
     *
     * Returns an empty string when absent so the connector can fall back to the
     * OAuth authenticator; a missing token is only a problem at request time.
     */
    private function staticAccessToken(): string
    {
        $token = $this->oauthConfig()['access_token'] ?? null;

        return is_string($token) && $token !== '' ? $token : '';
    }

    /**
     * The OAuth client for this Network, when the authorization flow is in use.
     *
     * Returns `null` when no subdomain/client ID is configured, or when a static
     * `oauth.access_token` is set — the static token always wins, since it is
     * the explicit "I already hold a token" path. See {@see self::tokenStore()}
     * for persisting the tokens this client issues.
     */
    public function oauthClient(): ?OAuthClient
    {
        if ($this->staticAccessToken() !== '') {
            return null;
        }

        $oauth = $this->oauthConfig();

        $clientId = $oauth['client_id'] ?? null;
        $subdomain = $this->subdomain();

        if (! is_string($clientId) || $clientId === '' || $subdomain === null) {
            return null;
        }

        $clientSecret = $oauth['client_secret'] ?? null;
        $redirectUri = $oauth['redirect_uri'] ?? null;
        $scopes = $oauth['scopes'] ?? null;

        $scopes = is_array($scopes)
            ? array_values(array_filter($scopes, static fn (mixed $scope): bool => is_string($scope)))
            : [];

        return new OAuthClient(
            connector: new OAuthConnector($subdomain),
            clientId: $clientId,
            clientSecret: is_string($clientSecret) && $clientSecret !== '' ? $clientSecret : null,
            redirectUri: is_string($redirectUri) && $redirectUri !== '' ? $redirectUri : null,
            scopes: $scopes,
        );
    }

    /**
     * The store used to persist OAuth tokens for this connection.
     *
     * One store safely serves every connection: the connection name is used as
     * the OAuth key, and the cache prefix is namespaced per connection too.
     * Returns `null` when no cache repository is available.
     */
    public function tokenStore(): ?TokenStore
    {
        if ($this->cache === null) {
            return null;
        }

        $ttl = $this->oauthConfig()['token_ttl'] ?? null;

        return new CacheTokenStore(
            cache: $this->cache,
            prefix: sprintf('mighty-networks.oauth.%s.', $this->name),
            ttl: is_int($ttl) ? $ttl : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function oauthConfig(): array
    {
        $oauth = $this->config['oauth'] ?? null;

        return is_array($oauth) ? $oauth : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayConfig(string $key): array
    {
        $value = $this->config[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
