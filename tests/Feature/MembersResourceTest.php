<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\NewMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberType;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;
use MCKLtech\MightyNetworks\Requests\Admin\Members\CreateMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\DeleteMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\FindMemberByEmailRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\GetMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ListMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\RemoveMemberFromNetworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ReplaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\SendPasswordResetRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\UpdateMemberRequest;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class MembersResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function memberPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'email' => 'jane@example.com',
            'member_type' => 'full',
            'permalink' => 'https://example.mn.co/members/42',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'San Francisco, CA',
            'bio' => 'Community builder',
            'avatar' => 'https://cdn.mn.co/avatars/42.jpg',
            'referral_count' => 3,
            'categories' => [['id' => 1, 'title' => 'Founders']],
            'ambassador_level' => 'gold',
            'last_visited_at' => '2024-04-01T08:00:00+00:00',
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_member_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $member = $resource->findById(42);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame(42, $member->id);
        $this->assertSame('jane@example.com', $member->email);
        $this->assertSame(MemberType::Full, $member->memberType);
        $this->assertSame('2024-01-15T10:30:00+00:00', $member->createdAt->toIso8601String());
        $this->assertSame(3, $member->referralCount);
        $this->assertSame([['id' => 1, 'title' => 'Founders']], $member->categories);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetMemberRequest
                && $request->resolveEndpoint() === 'networks/12345/members/42/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_email_sends_the_email_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $member = $resource->findByEmail('jane@example.com');

        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof FindMemberByEmailRequest
                && $request->resolveEndpoint() === 'networks/12345/members/by_email'
                && $request->query()->get('email') === 'jane@example.com';
        });
    }

    public function test_find_by_email_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Member not found'], 404)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByEmailOrNull('missing@example.com'));
    }

    public function test_all_returns_a_member_collection_for_the_first_page(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1]), $this->memberPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $members = $resource->all(perPage: 50);

        $this->assertInstanceOf(MemberCollection::class, $members);
        $this->assertCount(2, $members);
        $this->assertSame([1, 2], $members->map(static fn (Member $member): int => $member->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListMembersRequest
                && $request->resolveEndpoint() === 'networks/12345/members'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_maps_the_dto_to_a_snake_case_body_and_omits_nulls(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(['id' => 99]), 201)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $member = $resource->create(new NewMemberData(
            email: 'new@example.com',
            firstName: 'New',
            lastName: 'Member',
            role: MembershipRole::Host,
            memberType: MemberType::Full,
            sendWelcomeEmail: false,
        ));

        $this->assertSame(99, $member->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateMemberRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/members'
                && $request->body()->all() === [
                    'email' => 'new@example.com',
                    'first_name' => 'New',
                    'last_name' => 'Member',
                    'role' => 'host',
                    'member_type' => 'full',
                    'send_welcome_email' => false,
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $resource->update(42, new UpdateMemberData(firstName: 'Janet', role: MembershipRole::Moderator));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof UpdateMemberRequest) {
                return false;
            }

            return $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/members/42/'
                && $request->body()->all() === [
                    'first_name' => 'Janet',
                    'role' => 'moderator',
                ];
        });
    }

    public function test_replace_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $member = $resource->replace(42, new UpdateMemberData(
            email: 'janet@example.com',
            firstName: 'Janet',
            lastName: 'Doe',
            role: MembershipRole::Moderator,
        ));

        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof ReplaceMemberRequest) {
                return false;
            }

            return $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/members/42/'
                && $request->body()->all() === [
                    'email' => 'janet@example.com',
                    'first_name' => 'Janet',
                    'last_name' => 'Doe',
                    'role' => 'moderator',
                ];
        });
    }

    public function test_delete_sends_a_delete_to_the_member_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $resource->delete(42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteMemberRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/members/42/';
        });
    }

    public function test_remove_from_network_calls_the_network_membership_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $resource->removeFromNetwork(42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof RemoveMemberFromNetworkRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/members/42/network_membership';
        });
    }

    public function test_send_password_reset_posts_to_the_resets_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $resource->sendPasswordReset(42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof SendPasswordResetRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/members/42/password_resets';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Member not found'], 404)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(42);
    }

    public function test_a_401_throws_an_authentication_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Invalid API token'], 401)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $this->expectException(AuthenticationException::class);

        $resource->findById(42);
    }

    public function test_a_422_throws_a_validation_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Invalid role value'], 422)]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $this->expectException(ValidationException::class);

        $resource->create(new NewMemberData('a@b.com', 'A', 'B'));
    }

    public function test_it_paginates_the_items_links_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $members = [];

        foreach ($resource->paginate(perPage: 50)->items() as $member) {
            $this->assertInstanceOf(Member::class, $member);
            $members[] = $member->id;
        }

        $this->assertSame([1, 2], $members);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request->query()->get('page') === 2
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_it_paginates_the_data_meta_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->memberPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
            MockResponse::make([
                'data' => [$this->memberPayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $members = [];

        foreach ($resource->paginate(perPage: 1)->items() as $member) {
            $this->assertInstanceOf(Member::class, $member);
            $members[] = $member->id;
        }

        $this->assertSame([1, 2], $members);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_member(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Member $member) use (&$ids): void {
            $ids[] = $member->id;
        });

        $this->assertSame([1], $ids);
    }
}
