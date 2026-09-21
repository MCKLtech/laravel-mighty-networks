<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\BadgeCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\DataTransferObjects\NewBadgeData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateBadgeData;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\AddBadgeToMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\CreateBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\DeleteBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\GetBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\GetMemberBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ListBadgesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ListMemberBadgesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\RemoveBadgeFromMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ReplaceBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\UpdateBadgeRequest;
use MCKLtech\MightyNetworks\Resources\BadgesResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class BadgesResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function badgePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 8,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Top Contributor',
            'description' => 'Awarded for exceptional contributions',
            'color' => '#FFD700',
            'custom_field_id' => 100,
            'avatar_url' => 'https://cdn.mn.co/badges/8.png',
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_badge(): void
    {
        $mock = new MockClient([MockResponse::make($this->badgePayload(), 200)]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $badge = $resource->findById(8);

        $this->assertInstanceOf(Badge::class, $badge);
        $this->assertSame(8, $badge->id);
        $this->assertSame('https://cdn.mn.co/badges/8.png', $badge->avatarUrl);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetBadgeRequest
                && $request->resolveEndpoint() === 'networks/12345/badges/8/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_all_returns_a_badge_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->badgePayload(['id' => 1])], 'links' => ['next' => null]], 200),
        ]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $badges = $resource->all();

        $this->assertInstanceOf(BadgeCollection::class, $badges);
        $this->assertCount(1, $badges);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListBadgesRequest
                && $request->resolveEndpoint() === 'networks/12345/badges';
        });
    }

    public function test_create_sends_the_avatar_id_and_omits_nulls(): void
    {
        $mock = new MockClient([MockResponse::make($this->badgePayload(), 201)]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $resource->create(new NewBadgeData(title: 'Top Contributor', avatarId: 555));

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateBadgeRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/badges'
                && $request->body()->all() === [
                    'title' => 'Top Contributor',
                    'avatar_id' => 555,
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->badgePayload(), 200)]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $resource->update(8, new UpdateBadgeData(color: '#000000', avatarId: 999));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateBadgeRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/badges/8/'
                && $request->body()->all() === [
                    'color' => '#000000',
                    'avatar_id' => 999,
                ];
        });
    }

    public function test_replace_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->badgePayload(), 200)]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $badge = $resource->replace(8, new UpdateBadgeData(title: 'Replaced', avatarId: 999));

        $this->assertSame(8, $badge->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceBadgeRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/badges/8/'
                && $request->body()->all() === [
                    'title' => 'Replaced',
                    'avatar_id' => 999,
                ];
        });
    }

    public function test_delete_sends_a_delete_to_the_badge_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $resource->delete(8);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteBadgeRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/badges/8/';
        });
    }

    public function test_member_badges_are_listed_and_assigned(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->badgePayload(['id' => 3])], 'links' => ['next' => null]], 200),
            MockResponse::make($this->badgePayload(['id' => 3]), 201),
        ]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $badges = $resource->badgesForMember(7);
        $this->assertSame([3], $badges->map(static fn (Badge $badge): int => $badge->id)->all());

        $resource->addToMember(7, 3);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListMemberBadgesRequest
                && $request->resolveEndpoint() === 'networks/12345/members/7/badges';
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof AddBadgeToMemberRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/members/7/badges'
                && $request->body()->all() === ['badge_id' => 3];
        });
    }

    public function test_badge_for_member_and_removal_use_the_trailing_slash_path(): void
    {
        $mock = new MockClient([
            MockResponse::make($this->badgePayload(), 200),
            MockResponse::make([], 204),
        ]);

        $resource = new BadgesResource($this->admin($mock), '12345');

        $resource->badgeForMember(7, 8);
        $resource->removeFromMember(7, 8);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetMemberBadgeRequest
                && $request->resolveEndpoint() === 'networks/12345/members/7/badges/8/';
        });

        $mock->assertSent(function ($request): bool {
            return $request instanceof RemoveBadgeFromMemberRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/members/7/badges/8/';
        });
    }
}
