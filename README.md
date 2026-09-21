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
- [Common tasks](#common-tasks)
  - [Members](#members)
  - [Spaces and collections](#spaces-and-collections)
  - [Content](#content)
  - [Events](#events)
  - [Commerce](#commerce)
  - [Tags, badges, custom fields and polls](#tags-badges-custom-fields-and-polls)
  - [Assets](#assets)
  - [Multi-tenant connections](#multi-tenant-connections)
  - [Testing your integration](#testing-your-integration)
- [GraphQL](#graphql)
  - [Mutations](#mutations)
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

MightyNetworks::members()->findByEmail('jane@example.com');
MightyNetworks::posts()->all(spaceId: 42);
MightyNetworks::subscriptions()->all(memberId: 42);
```

Resources are grouped by area and reached through the connection. The
[Common tasks](#common-tasks) section below walks through the actions a developer reaches for
most often, with runnable snippets.

### Available resources

| Accessor | Covers |
| --- | --- |
| `network()` | Network details and the authenticated token's context |
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
| `abuseReports()` | Network abuse reports |
| `admin()` | The underlying Admin REST connector, for anything not yet wrapped |
| `graphql()` / `graphqlClient()` | The GraphQL connector and ergonomic client |

### Pagination

Both APIs paginate, with different conventions. The SDK normalises them.

```php
// First page only.
$members = MightyNetworks::members()->all(perPage: 100);

// Lazily iterate every page — each page is fetched on demand, so memory stays flat.
foreach (MightyNetworks::members()->paginate(perPage: 100)->items() as $member) {
    echo $member->email;
}

// Or push into a callback; each() is sugar over the lazy paginator.
MightyNetworks::members()->each(function (Member $member): void {
    sync_to_crm($member);
});
```

## Common tasks

Short, named-argument snippets for the actions you will actually perform. Signatures are shown by
example; the resource classes in `src/Resources` are the source of truth.

### Members

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\Enums\MemberType;

// Find by numeric id, or by email.
$member = MightyNetworks::members()->findById(42);
$member = MightyNetworks::members()->findByEmail('jane@example.com');

// A miss is often expected, so an OrNull variant exists to avoid a try/catch.
$member = MightyNetworks::members()->findByEmailOrNull('nobody@example.com'); // null

// Create a full member — they belong to the whole Network.
$member = MightyNetworks::members()->create(new NewMemberData(
    email: 'jane@example.com',
    firstName: 'Jane',
    lastName: 'Smith',
    memberType: MemberType::Full,
    sendWelcomeEmail: false,
));

// Create a limited member: they only see the spaces you name.
$member = MightyNetworks::members()->create(new NewMemberData(
    email: 'sam@example.com',
    firstName: 'Sam',
    lastName: 'Lee',
    memberType: MemberType::Limited,
    spaceIds: [$onboardingSpace->id, $communitySpace->id],
));

// update() is a PATCH — send only what changed. replace() is a PUT — send the full profile.
MightyNetworks::members()->update($member->id, new UpdateMemberData(firstName: 'Janet'));
MightyNetworks::members()->replace($member->id, new UpdateMemberData(
    email: 'janet@example.com',
    firstName: 'Janet',
    lastName: 'Lee',
));

// Remove from the Network (the account survives) vs delete the account outright.
MightyNetworks::members()->removeFromNetwork($member->id);
MightyNetworks::members()->delete($member->id);

// Trigger the standard password-reset email.
MightyNetworks::members()->sendPasswordReset($member->id);
```

Listing and pagination. `paginate()` returns a lazy paginator: each page is fetched only when the
iterator asks for it, so a roster of any size stays memory-flat.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;

// First page only.
$members = MightyNetworks::members()->all(perPage: 100);

// Every page, lazily.
foreach (MightyNetworks::members()->paginate(perPage: 100)->items() as $member) {
    sync_to_crm($member);
}

// Or let the SDK drive the loop.
MightyNetworks::members()->each(function (Member $member): void {
    sync_to_crm($member);
});
```

Exporting every member — the same lazy paginator, streamed to disk:

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;

$handle = fopen(storage_path('exports/members.csv'), 'w');

fputcsv($handle, ['id', 'email', 'member_type', 'joined_at']);

MightyNetworks::members()->each(function (Member $member) use ($handle): void {
    fputcsv($handle, [
        $member->id,
        $member->email,
        $member->memberType->value,
        $member->createdAt->toIso8601String(),
    ]);
});

fclose($handle);
```

A member's tags and badges are read from the member side and assigned by id:

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;

$tags = MightyNetworks::tags()->tagsForMember($member->id);
$badges = MightyNetworks::badges()->badgesForMember($member->id);

MightyNetworks::tags()->addToMember($member->id, $vipTag->id);
MightyNetworks::badges()->addToMember($member->id, $foundingMemberBadge->id);

MightyNetworks::tags()->removeFromMember($member->id, $vipTag->id);
MightyNetworks::badges()->removeFromMember($member->id, $foundingMemberBadge->id);
```

A member's plans and spaces come straight from the member API, while their purchases and
subscriptions give you the billing view (see [Commerce](#commerce)):

```php
$memberPlans = MightyNetworks::members()->plans($member->id, perPage: 100);
$memberSpaces = MightyNetworks::members()->spaces($member->id, perPage: 100);

$subscriptions = MightyNetworks::subscriptions()->all(memberId: $member->id);
$purchases = MightyNetworks::purchases()->all(memberId: $member->id);
```

### Spaces and collections

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewSpaceData;
use MCKLtech\MightyNetworks\Enums\CourseworkType;

// List spaces, or lazily page through all of them.
$spaces = MightyNetworks::spaces()->all(perPage: 100);

// Create a space by name.
$space = MightyNetworks::spaces()->create(new NewSpaceData(name: 'Introductions'));

// Add, remove, or ban a member. addMember() returns the member as they now
// appear inside the space; banMember() bans them from the whole Network.
MightyNetworks::spaces()->addMember($space->id, $member->id);
MightyNetworks::spaces()->removeMember($space->id, $member->id);
MightyNetworks::spaces()->banMember($space->id, $member->id, reason: 'Spam');

// Coursework in a course space, optionally filtered by type/status/parent.
$lessons = MightyNetworks::spaces()->courseworks(
    $courseSpace->id,
    type: CourseworkType::Lesson,
);

// Reorder the spaces inside a collection. Positions are assigned 1-based in
// array order; the reordered spaces come back.
$reordered = MightyNetworks::collections()->reorder($collection->id, [
    $orientationSpace->id,
    $generalSpace->id,
    $archiveSpace->id,
]);
```

### Content

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCommentData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPostData;
use MCKLtech\MightyNetworks\Enums\PostType;

// Create a post in a space. The optional $notify flag controls whether the
// Network is notified (it becomes a `notify` query parameter).
$post = MightyNetworks::posts()->create(new NewPostData(
    spaceId: $space->id,
    title: 'Welcome to the community',
    description: 'Please introduce yourself below.',
    postType: PostType::Post,
), notify: true);

// List posts, optionally scoped to one space.
$posts = MightyNetworks::posts()->all(spaceId: $space->id, perPage: 50);

// Comment on a post; replyToId turns it into a threaded reply.
$comment = MightyNetworks::posts()->createComment($post->id, new NewCommentData(
    text: 'Glad to be here!',
));

// React to a post with an emoji (repeat the call to change it), then remove it.
$reaction = MightyNetworks::posts()->react($post->id, '🔥');
MightyNetworks::posts()->removeReaction($post->id);

// Mute a post for one member (unfollow its notifications), or unmute again.
MightyNetworks::posts()->mute($post->id, $member->id);
MightyNetworks::posts()->unmute($post->id, $member->id);

// Delete a comment — comments are nested beneath their parent post.
MightyNetworks::comments()->delete($post->id, $comment->id);
```

### Events

```php
use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewEventData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewRsvpData;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;

// Create an event. title, starts_at, ends_at, event_type and space_id are required.
$event = MightyNetworks::events()->create(new NewEventData(
    title: 'Monthly AMA',
    startsAt: CarbonImmutable::parse('2026-10-01 18:00'),
    endsAt: CarbonImmutable::parse('2026-10-01 19:00'),
    eventType: 'online_meeting',
    spaceId: $space->id,
    timeZone: 'America/Los_Angeles',
));

// List events.
$events = MightyNetworks::events()->all(perPage: 50);

// RSVP a member to the event, then read the RSVP list back.
$rsvp = MightyNetworks::events()->createRsvp($event->id, new NewRsvpData(
    memberId: $member->id,
    status: RsvpStatus::Yes,
));

$rsvps = MightyNetworks::events()->rsvps($event->id, perPage: 100);
```

For a recurring event, pass `instanceAt` to `rsvps()` to narrow the list to one instance.

### Commerce

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewInviteData;
use MCKLtech\MightyNetworks\Enums\CancelTiming;

// List plans.
$plans = MightyNetworks::plans()->all(perPage: 100);

// Add a member directly to a free/nonpaid plan — access is granted immediately.
$proPlan = MightyNetworks::plans()->findById($proPlanId);
MightyNetworks::plans()->addMember($proPlan->id, $member->id);

// List subscriptions, optionally by status or member. The subscription's own id
// lives on the nested `subscription` detail, not on the top-level DTO.
$canceled = MightyNetworks::subscriptions()->all(status: 'canceled', perPage: 100);

foreach (MightyNetworks::subscriptions()->all(memberId: $member->id) as $subscription) {
    MightyNetworks::subscriptions()->cancel(
        $subscription->subscription->id,
        CancelTiming::EndOfBillingCycle,
    );
}

// List purchases and revoke access. The purchase id is nested under `purchase`;
// immediate: true skips the grace period.
$purchases = MightyNetworks::purchases()->all(memberId: $member->id);

MightyNetworks::purchases()->revoke($purchases[0]->purchase->id, immediate: true);

// Invite someone to the whole Network...
$invite = MightyNetworks::invites()->create(new NewInviteData(
    recipientEmail: 'newcomer@example.com',
    recipientFirstName: 'New',
    recipientLastName: 'Comer',
));

// ...or to a specific plan. Supply at least one of email/userId; couponId
// pre-applies a promo code at checkout.
$planInvite = MightyNetworks::plans()->createInvite(
    $proPlan->id,
    email: 'newcomer@example.com',
    message: 'Join us for the October cohort',
    couponId: $promoCode->id,
);
```

### Tags, badges, custom fields and polls

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldOptionData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPollData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewTagData;
use MCKLtech\MightyNetworks\Enums\CustomFieldPrivacy;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Enums\PollType;

// Create a tag, then assign it to a member (and remove it again).
$tag = MightyNetworks::tags()->create(new NewTagData(
    title: 'VIP',
    color: '#FF5733',
));

MightyNetworks::tags()->addToMember($member->id, $tag->id);
MightyNetworks::tags()->removeFromMember($member->id, $tag->id);

// Create a dropdown custom field. For dropdown types you can pass the initial
// option texts inline...
$field = MightyNetworks::customFields()->create(new NewCustomFieldData(
    title: 'Which cohort are you in?',
    responseType: CustomFieldResponseType::DropdownSingleSelect,
    privacy: CustomFieldPrivacy::Public,
    options: ['October 2026', 'January 2027'],
));

// ...or add options one at a time later. list them with options()/paginateOptions().
$option = MightyNetworks::customFields()->createOption($field->id, new NewCustomFieldOptionData(
    title: 'April 2027',
));

// Store a member's answer. Pass only the value fields for the field's type
// (text, number, booleanValue, date, url, latitude/longitude, locations, ...).
MightyNetworks::customFields()->createAnswer(
    $field->id,
    $member->id,
    text: 'October 2026',
);

// Create a multiple-choice poll in a space.
$poll = MightyNetworks::polls()->create(new NewPollData(
    spaceId: $space->id,
    title: 'Which topic should we cover next?',
    pollType: PollType::MultipleChoice,
    choices: ['Onboarding', 'Retention', 'Pricing'],
    notify: true,
));
```

### Assets

Asset uploads are `multipart/form-data`. There are three variants, all returning an `Asset` whose
`id` other endpoints reference. The SDK checks the 25 MB cap client-side before sending.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewBadgeData;
use MCKLtech\MightyNetworks\Enums\AssetStyle;

// From a local file path — the filename is taken from the path.
$asset = MightyNetworks::assets()->upload(
    storage_path('app/badges/top-contributor.png'),
    assetStyle: AssetStyle::Avatar,
);

// From raw base64 contents already in memory.
$asset = MightyNetworks::assets()->uploadBase64(
    $base64FromUpload,
    filename: 'top-contributor.png',
    contentType: 'image/png',
);

// Let the API fetch the file from a public URL itself.
$asset = MightyNetworks::assets()->uploadFromUrl('https://cdn.example.com/badge.png');
```

The returned asset id is what the rest of the API references. A badge, for example, takes its image
as an asset id:

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\DataTransferObjects\NewBadgeData;

$badge = MightyNetworks::badges()->create(new NewBadgeData(
    title: 'Top contributor',
    avatarId: $asset->id,
));
```

> The Admin REST `POST posts` payload accepts only `space_id`, `title`, `description` and
> `post_type`, so there is no SDK method that attaches an uploaded asset to a post. Video and audio
> recordings likewise must go through the GraphQL `createUploadSession` flow rather than
> `assets()`.

### Multi-tenant connections

Loop over tenants inside a queued job, building a runtime connection from each tenant's stored
credentials. The inherited rate limits and retry policy still apply, and the job middleware keeps
rate limiting queue-aware rather than blocking a worker.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;

final class SyncTenantMembers implements ShouldQueue
{
    public int $tries = 10;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new ApiRateLimited];
    }

    public function handle(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            // Credentials come from the tenant row; everything else is inherited
            // from your configured connection. Secrets are never inherited.
            $client = MightyNetworks::withCredentials(
                $tenant->mn_admin_token,
                $tenant->mn_network_id,
            );

            $client->members()->each(function (Member $member) use ($tenant): void {
                $tenant->members()->updateOrCreate(
                    ['mn_id' => $member->id],
                    [
                        'email' => $member->email,
                        'member_type' => $member->memberType->value,
                        'joined_at' => $member->createdAt,
                    ],
                );
            });
        });
    }
}
```

To share (or deliberately isolate) the cache and rate-limit counters, build the connection with
`make()` and pass a cache repository explicitly. The same repository backs both response caching
and rate-limit storage, and `name` namespaces any OAuth tokens for the connection:

```php
use Illuminate\Support\Facades\Cache;
use MCKLtech\MightyNetworks\MightyNetworks;

$client = MightyNetworks::make(
    adminToken: $tenant->mn_admin_token,
    networkId: $tenant->mn_network_id,
    overrides: ['rate_limits' => ['per_minute' => 30]],
    cache: Cache::store('redis'),
    name: "tenant-{$tenant->id}",
);
```

### Testing your integration

There is no sandbox, so mock at the connector boundary with Saloon's `MockClient` — the resource
methods run unchanged and only the transport is faked. This is exactly how this package tests
itself.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\Requests\Admin\Members\GetMemberRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

$mock = new MockClient([
    MockResponse::make([
        'id' => 42,
        'email' => 'jane@example.com',
        'member_type' => 'full',
        // ...the rest of the member payload
    ], 200),
]);

$client = MightyNetworks::make('test-admin-token', 12345);
$client->admin()->withMockClient($mock);

$this->assertSame('jane@example.com', $client->members()->findById(42)->email);

$mock->assertSent(function (GetMemberRequest $request): bool {
    return $request->resolveEndpoint() === 'networks/12345/members/42/';
});
```

To swap the whole SDK for a stub, point the facade at a runtime connection. This replaces the
facade root for the duration of the test, so app code calling `MightyNetworks::members()` (and the
other resource accessors) hits the stub. Note that `MightyNetworks::connection()` is no longer
available on the swapped root.

```php
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\MightyNetworks as MightyNetworksConnection;

MightyNetworks::swap(MightyNetworksConnection::make('stub-token', 12345, [
    'base_url' => 'https://stub.internal',
]));
```

## GraphQL

The GraphQL API signals application errors with **HTTP 200** and a populated `errors` array. The
SDK inspects the body rather than trusting the status code, so failed queries throw.

```php
use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\Requests\GraphQL\NetworkMembersQuery;

// The authenticated viewer, or null when the token has no member node.
$me = MightyNetworks::graphqlClient()->me();
$me?->resourceId;   // the integer ID used by the Admin REST API

// The Network's public settings. GraphQL calls this field `title`, not `name`.
$network = MightyNetworks::graphqlClient()->network();
echo $network->title;

// A sorted, cursor-paginated roster. NetworkMembersQuery implements the
// pagination contract; paginate() walks pageInfo.endCursor for you.
$roster = new NetworkMembersQuery(
    networkIdOrSubdomain: 'acme',
    first: 50,
    sort: MemberSort::DateJoined,
    sortOrder: SortOrder::Desc,
);

foreach (MightyNetworks::graphqlClient()->paginate($roster, perPage: 50)->items() as $member) {
    echo $member->email;
}

// Full control when you need a query the SDK does not wrap. raw() returns the
// Saloon response, so read the decoded body yourself.
$response = MightyNetworks::graphqlClient()->raw(
    'query NetworkHeader { network { id title slug } }',
);

$networkTitle = $response->json()['data']['network']['title'];

// The typed first page is also available without constructing a request.
$recent = MightyNetworks::graphqlClient()->members(
    perPage: 50,
    sort: MemberSort::LastVisit,
    sortOrder: SortOrder::Desc,
);
```

### Mutations

Mighty Networks documents **185 mutations**. Every one is reachable, and the 20 most common have typed
request classes with typed input DTOs.

**By registry case** — `GraphQLMutation` carries all 185 operations together with each one's input and
payload type, so you rarely need to hand-write a document:

```php
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\Facades\MightyNetworks;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;

$response = MightyNetworks::graphqlClient()->mutate(
    operation: GraphQLMutation::CreateMember,
    variables: ['input' => [
        'email' => 'jane@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Smith',
    ]],
    selection: MutationSelections::member(),
);

$payload = $response->dto();      // GraphQLMutationPayload

$payload->succeeded();
$payload->firstError();
$payload->member()?->id;
```

**With a typed request** — the ergonomic path for the wrapped mutations:

```php
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateMemberInput;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateMemberMutation;

$payload = MightyNetworks::graphqlClient()
    ->query(new CreateMemberMutation(
        networkIdOrSubdomain: 'acme',
        input: new CreateMemberInput(
            email: 'jane@example.com',
            firstName: 'Jane',
            lastName: 'Smith',
            sendWelcomeEmail: false,
        ),
    ))
    ->dto();

$memberId = $payload->member()?->id;
```

Typed mutations cover `createMember`, `updateMember`, `deleteMember`, `banMember`, `createPost`,
`updatePost`, `deletePost`, `createComment`, `deleteComment`, `createReaction`, `createSpace`,
`updateSpace`, `createEvent`, `updateEvent`, `createRsvp`, `createInvites`,
`createPaymentPlanMembership`, `createSpaceMemberships`, `createWebhookCallback` and
`updateWebhookCallback`. Anything else goes through `mutate()` with a `GraphQLMutation` case.

Read the payload generically when a mutation's shape varies:

```php
$payload->deletedId();      // delete mutations
$payload->count();          // bulk mutations
$payload->outcome();        // mutations that report an outcome
$payload->entity('plan');   // any other payload key, as a raw array
```

To fetch a billing plan — the last of the five query roots:

```php
$plan = MightyNetworks::graphqlClient()->billingPlan('community');
```

> **Enum values differ between the two APIs.** GraphQL spells `MembershipRole` in upper case
> (`CONTRIBUTOR`, `HOST`, `MODERATOR`) whereas the Admin REST API uses lower case (`contributor`,
> `host`, `moderator`). Mutations therefore accept the GraphQL spelling as a plain string; passing a
> REST-backed enum would emit an invalid value.

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
network calls — everything is mocked with Saloon's `MockClient`. See
[Testing your integration](#testing-your-integration) for how to mock the SDK in your own app.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@mckl.tech instead of using the
issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
