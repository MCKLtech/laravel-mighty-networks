<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The connection that will be resolved when no explicit connection name is
    | given. Agencies commonly manage several Mighty Networks at once, so every
    | key below lives inside the "connections" array and can be named freely.
    |
    */

    'default' => env('MIGHTY_NETWORKS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Each entry represents a single Mighty Network. The array key is the
    | connection name you pass to `MightyNetworks::connection('name')`.
    |
    */

    'connections' => [

        'default' => [

            // The numeric (or slug) Network ID used throughout the Admin API paths.
            'network_id' => env('MIGHTY_NETWORKS_NETWORK_ID'),

            // The Network subdomain, e.g. "acme" for acme.mn.co (GraphQL only).
            'subdomain' => env('MIGHTY_NETWORKS_SUBDOMAIN'),

            // Long-lived Admin API key sent as `Authorization: Bearer <key>`.
            'admin_token' => env('MIGHTY_NETWORKS_ADMIN_TOKEN'),

            // API host. Mighty Networks serves both APIs from this host.
            'base_url' => env('MIGHTY_NETWORKS_BASE_URL', 'https://api.mn.co'),

            // A non-empty User-Agent is mandatory: without one api.mn.co returns
            // an HTML bot challenge with HTTP 403. Use `name/version (+url)`.
            'user_agent' => env(
                'MIGHTY_NETWORKS_USER_AGENT',
                'laravel-mighty-networks/1.0 (+https://github.com/MCKLtech/laravel-mighty-networks)',
            ),

            /*
            | OAuth 2.0 credentials for the GraphQL (Mighty) API. When both
            | `client_id` and a `subdomain` are set, the SDK uses the
            | authorization-code + PKCE flow and persists tokens through the
            | `TokenStore` contract, refreshing them transparently. A static
            | `access_token` is also supported for long-lived tokens.
            */
            'oauth' => [
                'client_id' => env('MIGHTY_NETWORKS_CLIENT_ID'),
                'client_secret' => env('MIGHTY_NETWORKS_CLIENT_SECRET'),
                'redirect_uri' => env('MIGHTY_NETWORKS_REDIRECT_URI'),
                'scopes' => [],
                'access_token' => env('MIGHTY_NETWORKS_ACCESS_TOKEN'),

                // Optional named Laravel cache store for OAuth tokens (null = default).
                'store' => null,

                // Cached token lifetime in seconds (null = keep forever).
                'token_ttl' => null,
            ],

            /*
            | Retry configuration for the core Saloon `HasTries` plugin.
            | `tries` is the number of attempts (null disables retries).
            | `interval` is the wait between attempts, in milliseconds.
            */
            'retry' => [
                'tries' => 3,
                'interval' => 500,
                'exponential_backoff' => true,
            ],

            /*
            | Rate limits. Mighty Networks uses monthly quotas with no rate-limit
            | response headers, so client-side throttling is config-driven.
            | `per_minute` is a conservative local ceiling; `per_day` is optional.
            | `store` is an optional named Laravel cache store.
            */
            'rate_limits' => [
                'enabled' => true,
                'per_minute' => 60,
                'per_day' => null,
                'store' => null,
            ],

            /*
            | Response caching (GET requests only). `store` is an optional named
            | Laravel cache store; `ttl` is the lifetime in seconds.
            */
            'cache' => [
                'enabled' => false,
                'store' => null,
                'ttl' => 300,
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Webhook receiving routes are registered only when `enabled` is true.
    | Mighty Networks does NOT HMAC-sign webhooks: the configured `secret` is
    | delivered as `Authorization: Bearer <secret>` and compared in constant
    | time. `tolerance` is the accepted age of `event_timestamp`, in seconds.
    |
    */

    'webhooks' => [
        'enabled' => false,
        'path' => 'webhooks/mighty-networks',
        'secret' => env('MIGHTY_NETWORKS_WEBHOOK_SECRET'),
        'tolerance' => 300,
    ],

];
