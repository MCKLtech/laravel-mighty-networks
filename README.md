# Laravel Mighty Networks

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mckltech/laravel-mighty-networks.svg?style=flat-square)](https://packagist.org/packages/mckltech/laravel-mighty-networks)
[![Tests](https://github.com/MCKLtech/laravel-mighty-networks/actions/workflows/run-tests.yml/badge.svg)](https://github.com/MCKLtech/laravel-mighty-networks/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mckltech/laravel-mighty-networks.svg?style=flat-square)](https://packagist.org/packages/mckltech/laravel-mighty-networks)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A Laravel SDK for the **Mighty Networks** platform, built on [Saloon](https://docs.saloon.dev/).

It wraps both Mighty Networks APIs behind a single, expressive, Laravel-native interface:

- **Admin REST API** — long-lived API key, acts as a network administrator.
- **Mighty API (GraphQL)** — OAuth 2.0, acts as an authorized member or host.

## Contents

- [Why this package](#why-this-package)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Runtime credentials](#runtime-credentials)
- [Usage](#usage)
- [GraphQL](#graphql)
- [OAuth 2.0](#oauth-20)
- [Webhooks](#webhooks)
- [Queues and rate limiting](#queues-and-rate-limiting)
- [Caching](#caching)
- [Error handling](#error-handling)
- [Mighty Networks quirks this package handles](#mighty-networks-quirks-this-package-handles)
- [Testing](#testing)

## Why this package

Mighty Networks ships **two independent APIs** with different credentials, pagination conventions
and error semantics. This SDK absorbs all of that:

- One entry point, resource-grouped and discoverable.
- Typed, immutable **DTOs** instead of raw arrays.
- **Pagination** that just works, for both cursor (GraphQL) and page-based (REST) APIs.
- **Rate limiting** backed by your Laravel cache, including queue-aware job middleware.
- **Caching**, **retries** with exponential backoff, and typed exceptions.
- **Webhooks** — receiving, secret verification, idempotency and queueing out of the box.
- Support for **multiple networks**, from configuration or supplied at runtime.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Saloon 4

## Installation

```bash
composer require mckltech/laravel-mighty-networks
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=mighty-networks-config
```

## Configuration

Add your network credentials to `.env`:

```dotenv
MIGHTY_NETWORKS_NETWORK_ID=12345
MIGHTY_NETWORKS_ADMIN_TOKEN=your-admin-api-key
```

You can generate an admin API key from **Admin → Settings → API Keys** inside your Mighty Networks
network.

| Key | Description |
| --- | --- |
| `network_id` | Your numeric Network ID (or slug). Used in every Admin API path. |
| `admin_token` | Long-lived Admin API key. Sent as `Authorization: Bearer`. |
| `subdomain` | Your network subdomain (e.g. `acme` for `acme.mn.co`). GraphQL and OAuth only. |
| `base_url` | API host. Defaults to `https://api.mn.co`. |
| `user_agent` | **Mandatory.** See [quirks](#mighty-networks-quirks-this-package-handles). |
| `oauth.*` | OAuth 2.0 application credentials for the GraphQL API. |
| `retry.*` | Attempts, interval and exponential backoff. |
| `rate_limits.*` | Client-side throttling (Mighty Networks exposes no rate-limit headers). |
| `cache.*` | Response caching (GET requests only). |
| `webhooks.*` | Webhook receiving route, secret and timestamp tolerance. |

### Multiple networks

Agencies commonly manage several communities at once. Every setting lives inside a named
connection, mirroring `Storage::disk()` and `DB::connection()`:

```php
MightyNetworks::connection('acme')->members()->findByEmail('jane@example.com');
MightyNetworks::connection('globex')->posts()->all();
```

## Runtime credentials

Credentials do not have to come from the config file. When each tenant stores its own network
credentials — in a database, a job payload, a form request — build a connection at runtime:

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;

// Inherits non-credential settings (base URL, User-Agent, retry, rate limits)
// from your configured connection, and is wired for rate limiting.
$client = MightyNetworks::withCredentials($tenant->mn_admin_token, $tenant->mn_network_id);
```

A runtime connection is a fully-fledged `MightyNetworks` instance — every resource is available:

```php
$client->members()->findByEmail('jane@example.com');
$client->posts()->paginate();
```

### Other ways to build a connection

```php
use MCKLtech\MightyNetworks\MightyNetworks;

// Container-free — handy in jobs, commands and tests.
$client = MightyNetworks::make($adminToken, $networkId);

// With overrides for any config key.
$client = MightyNetworks::make($adminToken, $networkId, [
    'base_url' => 'https://eu.api.mn.co',
]);

// From a complete config array.
$client = MightyNetworks::withConfig($tenant->mn_config, 'acme');
```

> **Secrets are never inherited.** `withCredentials()` deliberately strips `admin_token` and
> `oauth.access_token` from the base connection before applying your credentials, so a runtime
> tenant can never pick up another network's token. OAuth *application* credentials
> (`client_id`, `client_secret`, `redirect_uri`, `scopes`) are inherited, since those are
> normally shared app-wide.

## Usage

Every call returns a typed DTO or a typed collection — never a raw array.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewMemberData;
use MCKLtech\MightyNetworks\Enums\MemberType;

// Find by email — the everyday case.
$member = MightyNetworks::members()->findByEmail('jane@example.com');
$member->id;
$member->email;
$member->memberType;      // MemberType enum
$member->joinedAt;        // CarbonImmutable

// A lookup that can legitimately miss, without a try/catch.
$member = MightyNetworks::members()->findByEmailOrNull('nobody@example.com'); // null

// Create.
$member = MightyNetworks::members()->create(new NewMemberData(
    email: 'jane@example.com',
    firstName: 'Jane',
    lastName: 'Smith',
    memberType: MemberType::Limited,
    spaceIds: [111, 222],
    sendWelcomeEmail: false,
));

// Update (PATCH) and replace (PUT).
MightyNetworks::members()->update($member->id, new UpdateMemberData(firstName: 'Janet'));
MightyNetworks::members()->replace($member->id, new UpdateMemberData(email: 'janet@example.com'));

// Remove from the network, or delete entirely.
MightyNetworks::members()->removeFromNetwork($member->id);
MightyNetworks::members()->delete($member->id);
```

### Available resources

| Accessor | Covers |
| --- | --- |
| `members()` | Members, roles, tags, badges, plans, spaces, password resets |
| `spaces()` | Spaces, space members, banning, courseworks |
| `collections()` | Space collections and ordering |
| `posts()` | Posts, muting, nested comments and reactions |
| `comments()` | Comment lookup, deletion and reactions |
| `events()` | Events and RSVPs |
| `plans()` | Plans, plan members and plan invites |
| `subscriptions()` | Subscriptions and cancellation |
| `purchases()` | Purchases and revocation |
| `invites()` | Network invites |
| `tags()` / `badges()` | Tag and badge catalogues, member assignment |
| `customFields()` | Custom fields, their options, and member answers |
| `polls()` | Polls |
| `assets()` | Multipart asset uploads (25 MB cap) |
| `admin()` | The underlying Admin REST connector, for anything not yet wrapped |
| `graphql()` / `graphqlClient()` | The GraphQL connector and ergonomic client |

### Pagination

Both APIs paginate, with different conventions. The SDK normalises them.

```php
// First page only.
$members = MightyNetworks::members()->all(perPage: 100);

// Lazily iterate every page — memory stays flat.
foreach (MightyNetworks::members()->paginate(perPage: 100)->items() as $member) {
    echo $member->email;
}

// Or push into a callback.
MightyNetworks::members()->each(function (Member $member): void {
    sync_to_crm($member);
});
```

## GraphQL

The GraphQL API signals application errors with **HTTP 200** and a populated `errors` array. The
SDK inspects the body rather than trusting the status code, so failed queries throw.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;

$me = MightyNetworks::graphqlClient()->me();
$network = MightyNetworks::graphqlClient()->network();

// Cursor-paginated roster, paged from pageInfo.endCursor automatically.
foreach (MightyNetworks::graphqlClient()->paginate($request) as $page) {
    // ...
}

// Full control when you need a query the SDK does not wrap.
$response = MightyNetworks::graphqlClient()->raw('query { network { id name } }');
```

GraphQL enforces a **query complexity ceiling of 1500**; requests exceeding it are rejected before
execution and surface as `ComplexityException`. Root connections cap at 50 nodes per page and nested
connections at 25 — oversized `first:` arguments are clamped by the API.

## OAuth 2.0

The GraphQL API authenticates as a user via OAuth 2.0. Tokens are stored through a `TokenStore`
contract (Laravel-cache-backed by default) and **refreshed transparently** when they expire,
including persisting the rotated refresh token.

```php
$oauth = MightyNetworks::connection('acme')->oauthClient();

// Redirect the user to the community host to authorize.
$url = $oauth->authorizationUrl($state, $codeChallenge, scopes: ['read:network']);

// Exchange the returned code (PKCE).
$token = $oauth->exchangeCode($code, $codeVerifier);
$store->put('acme', $token);
```

Once a token is stored, GraphQL requests authenticate themselves — you never touch the token again.
A rejected refresh throws `AuthenticationException`, meaning the user must re-authorize.

> A configured static `access_token` always takes precedence over the authorization flow. That is the
> path for long-lived tokens issued outside a user-facing flow.

## Webhooks

Enable webhooks and set a secret:

```dotenv
MIGHTY_NETWORKS_WEBHOOK_SECRET=your-shared-secret
```

```php
// config/mighty-networks.php
'webhooks' => [
    'enabled' => true,
    'path' => 'webhooks/mighty-networks',
    'secret' => env('MIGHTY_NETWORKS_WEBHOOK_SECRET'),
    'tolerance' => 300,
],
```

The route `POST /webhooks/mighty-networks` is then registered and will:

1. Verify the `Authorization: Bearer` secret in **constant time** (`hash_equals`).
2. Reject deliveries whose `event_timestamp` falls outside the tolerance window (replay defence).
3. Dispatch a queued job that is **idempotent per `event_id`**, so retries cannot double-process.
4. Fire a `WebhookReceived` event for your application to listen to.

Returns a fast `202` so Mighty Networks does not retry unnecessarily.

```php
use MCKLtech\MightyNetworks\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
    if ($event->envelope->eventType === WebhookEventType::MemberJoined) {
        // ...
    }
});
```

> **Mighty Networks webhooks are not HMAC-signed.** There is no signature header. The configured
> secret arrives as a Bearer token and is compared in constant time. Do not reach for Stripe-style
> signature verification.

Unknown event types are handled gracefully — they will not 500. The full 47-value
`WebhookEventType` enum is provided, with normalisation across the three spellings Mighty Networks
uses (`POST_CREATED`, `PostCreated`, `MemberJoinedHook`).

## Queues and rate limiting

Mighty Networks enforces **monthly quotas with no rate-limit headers**, so the SDK applies a
configurable client-side ceiling (backed by your Laravel cache) and honours `Retry-After` on a 429.

Inside a queued job, use the plugin's middleware to have the job released and retried rather than
sleeping:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;

final class SyncMembers implements ShouldQueue
{
    public int $tries = 10;

    public function middleware(): array
    {
        return [new ApiRateLimited];
    }
}
```

This makes rate limiting work *with* your queue rather than blocking a worker.

## Caching

Cache read requests against your Laravel cache — Memory, Redis, Database, whatever you have
configured:

```php
'cache' => [
    'enabled' => true,
    'store' => 'redis',
    'ttl' => 300,
],
```

Only successful `GET` requests are cached. Caching also reduces pressure on your monthly API quota.

## Error handling

Every failure surfaces as a typed exception, all extending `MightyNetworksException`:

| Exception | Raised when |
| --- | --- |
| `AuthenticationException` | 401, `UNAUTHENTICATED`, or a rejected OAuth refresh |
| `ForbiddenException` | 403 or `FORBIDDEN` (missing scope or permission) |
| `NotFoundException` | 404 or `NOT_FOUND` |
| `ValidationException` | 422 or `BAD_USER_INPUT` |
| `RateLimitException` | 429 or `THROTTLED` |
| `ServerException` | 5xx |
| `GraphQLException` | A GraphQL `errors` array; exposes `errors()` |
| `ComplexityException` | A GraphQL query exceeded the complexity ceiling |

```php
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;

try {
    $member = MightyNetworks::members()->findByEmail($email);
} catch (NotFoundException) {
    // No such member (or not visible to this credential).
}
```

The exception classes extend Saloon's own tree, so **retries keep working**: transient network
failures and 5xx responses are retried with exponential backoff, while 4xx responses fail fast.

Secrets are never logged: tokens are marked with `#[\SensitiveParameter]`.

## Mighty Networks quirks this package handles

These are real traps, handled for you rather than documented at you:

- **A `User-Agent` header is mandatory.** Omit it and `api.mn.co` returns an *HTML bot challenge with
  HTTP 403* — which surfaces as a confusing JSON parse error. The SDK always sends one.
- **The GraphQL API returns HTTP 200 for failures.** Errors live in the response body.
- **Trailing slashes are inconsistent.** `subscriptions/{id}` and `spaces/{id}` have **no** trailing
  slash while every sibling resource does. Each path is built to match the spec exactly.
- **The Admin REST pagination envelope is undocumented and inconsistent** — two different shapes
  appear in the spec. Both are supported.
- **Webhooks are not HMAC-signed** — see [Webhooks](#webhooks).
- **The official OpenAPI spec is broken**: 21 `*Paged` schemas are referenced but never defined, so
  standard code generators fail on all list endpoints. The presenters here are hand-written.
- **Member emails may be empty or masked** (`a***@***.***`) depending on the network's plan. The
  SDK never attempts to unmask them.
- **Rate limits are monthly quotas, not per-minute**, and no rate-limit headers are returned.

## Testing

```bash
composer test        # PHPUnit
composer phpstan     # PHPStan level 8
composer pint:test   # code style
composer check       # all three
```

There is **no sandbox or test network** for Mighty Networks, so the test suite never makes real
network calls — everything is mocked with Saloon's `MockClient`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@mckl.tech instead of using the
issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
