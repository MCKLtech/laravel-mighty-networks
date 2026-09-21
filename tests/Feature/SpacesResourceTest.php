<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\CourseworkCollection;
use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\Collections\SpaceCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewSpaceData;
use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateSpaceData;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\AddSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\BanSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\CreateCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\CreateSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\DeleteCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\DeleteSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListCourseworksRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListSpaceMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListSpacesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\RemoveSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateSpaceRequest;
use MCKLtech\MightyNetworks\Resources\SpacesResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class SpacesResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function spacePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Announcements',
            'collection_id' => 3,
        ], $overrides);
    }

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
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function courseworkPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 100,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'space_id' => 7,
            'type' => 'lesson',
            'parent_id' => 55,
            'parent_type' => 'section',
            'title' => 'Welcome',
            'description' => 'Intro lesson',
            'status' => 'posted',
            'position' => 1,
            'completion_criteria' => 'visited',
            'unlocking_criteria' => 'sequential',
            'children_count' => 0,
            'permalink' => 'https://example.mn.co/courses/7/lessons/100',
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_space_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->spacePayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $space = $resource->findById(7);

        $this->assertInstanceOf(Space::class, $space);
        $this->assertSame(7, $space->id);
        $this->assertSame('Announcements', $space->name);
        $this->assertSame(3, $space->collectionId);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetSpaceRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces/7'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Space not found'], 404)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(999));
    }

    public function test_all_returns_a_space_collection_for_the_first_page(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->spacePayload(['id' => 1]), $this->spacePayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $spaces = $resource->all(perPage: 50);

        $this->assertInstanceOf(SpaceCollection::class, $spaces);
        $this->assertCount(2, $spaces);
        $this->assertSame([1, 2], $spaces->map(static fn (Space $space): int => $space->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListSpacesRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_posts_the_name_and_returns_a_space(): void
    {
        $mock = new MockClient([MockResponse::make($this->spacePayload(['id' => 99]), 201)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $space = $resource->create(new NewSpaceData(name: 'New Space'));

        $this->assertSame(99, $space->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateSpaceRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/spaces'
                && $request->body()->all() === ['name' => 'New Space'];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->spacePayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->update(7, new UpdateSpaceData(name: 'Renamed', collectionId: 4));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateSpaceRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/spaces/7'
                && $request->body()->all() === ['name' => 'Renamed', 'collection_id' => 4];
        });
    }

    public function test_delete_sends_a_delete_to_the_space_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->delete(7);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteSpaceRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/spaces/7';
        });
    }

    public function test_members_returns_a_member_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1]), $this->memberPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $members = $resource->members(7, perPage: 50);

        $this->assertInstanceOf(MemberCollection::class, $members);
        $this->assertCount(2, $members);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListSpaceMembersRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_find_member_returns_a_typed_member(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $member = $resource->findMember(7, 42);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetSpaceMemberRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members/42/';
        });
    }

    public function test_find_member_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $this->assertNull($resource->findMemberOrNull(7, 42));
    }

    public function test_add_member_posts_with_the_user_id_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $member = $resource->addMember(7, 42);

        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof AddSpaceMemberRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members'
                && $request->query()->get('user_id') === 42;
        });
    }

    public function test_update_member_sends_a_patch_with_the_role(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->updateMember(7, 42, new UpdateMemberData(role: MembershipRole::Moderator));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateSpaceMemberRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members/42/'
                && $request->body()->all() === ['role' => 'moderator'];
        });
    }

    public function test_remove_member_sends_a_delete(): void
    {
        $mock = new MockClient([MockResponse::make([], 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->removeMember(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof RemoveSpaceMemberRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members/42/';
        });
    }

    public function test_ban_member_posts_to_the_ban_endpoint_with_a_reason(): void
    {
        $mock = new MockClient([MockResponse::make([], 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->banMember(7, 42, reason: 'Spam');

        $mock->assertSent(function ($request): bool {
            return $request instanceof BanSpaceMemberRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members/42/ban'
                && $request->body()->all() === ['ban_reason' => 'Spam'];
        });
    }

    public function test_courseworks_returns_a_typed_collection_with_filters(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->courseworkPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 1, 'total_count' => 1, 'per_page' => 25],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $courseworks = $resource->courseworks(
            7,
            type: CourseworkType::Lesson,
            status: CourseworkStatus::Posted,
            parentId: 55,
        );

        $this->assertInstanceOf(CourseworkCollection::class, $courseworks);
        $this->assertCount(1, $courseworks);
        $this->assertInstanceOf(Coursework::class, $courseworks->first());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCourseworksRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks'
                && $request->query()->get('type') === 'lesson'
                && $request->query()->get('status') === 'posted'
                && $request->query()->get('parent_id') === 55;
        });
    }

    public function test_find_coursework_returns_a_typed_coursework(): void
    {
        $mock = new MockClient([MockResponse::make($this->courseworkPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $coursework = $resource->findCoursework(7, 100);

        $this->assertInstanceOf(Coursework::class, $coursework);
        $this->assertSame(100, $coursework->id);
        $this->assertSame(CourseworkType::Lesson, $coursework->type);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCourseworkRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks/100/';
        });
    }

    public function test_find_coursework_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $this->assertNull($resource->findCourseworkOrNull(7, 100));
    }

    public function test_create_coursework_posts_the_dto_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->courseworkPayload(['id' => 101]), 201)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $coursework = $resource->createCoursework(7, new NewCourseworkData(
            type: CourseworkType::Quiz,
            title: 'Pop Quiz',
            status: CourseworkStatus::Hidden,
        ));

        $this->assertSame(101, $coursework->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCourseworkRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks'
                && $request->body()->all() === [
                    'type' => 'quiz',
                    'title' => 'Pop Quiz',
                    'status' => 'hidden',
                ];
        });
    }

    public function test_update_coursework_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->courseworkPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->updateCoursework(7, 100, new UpdateCourseworkData(title: 'Renamed'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateCourseworkRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks/100/'
                && $request->body()->all() === ['title' => 'Renamed'];
        });
    }

    public function test_delete_coursework_sends_a_delete(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $resource->deleteCoursework(7, 100);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCourseworkRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks/100/';
        });
    }

    public function test_it_paginates_spaces_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->spacePayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->spacePayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 50)->items() as $space) {
            $this->assertInstanceOf(Space::class, $space);
            $ids[] = $space->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_space(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->spacePayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Space $space) use (&$ids): void {
            $ids[] = $space->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_each_member_runs_a_callback_over_every_space_member(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        $resource->eachMember(7, static function (Member $member) use (&$ids): void {
            $ids[] = $member->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_each_coursework_runs_a_callback_over_every_item(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->courseworkPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        $resource->eachCoursework(7, static function (Coursework $coursework) use (&$ids): void {
            $ids[] = $coursework->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Space not found'], 404)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(7);
    }

    public function test_paginate_members_yields_space_members_across_pages(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->memberPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginateMembers(7, perPage: 10)->items() as $member) {
            $this->assertInstanceOf(Member::class, $member);
            $ids[] = $member->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request instanceof ListSpaceMembersRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_paginate_courseworks_yields_coursework_across_pages(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->courseworkPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->courseworkPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginateCourseworks(7, type: CourseworkType::Lesson, perPage: 10)->items() as $coursework) {
            $this->assertInstanceOf(Coursework::class, $coursework);
            $ids[] = $coursework->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCourseworksRequest
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks'
                && $request->query()->get('type') === 'lesson'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_all_returns_an_empty_typed_collection_for_an_empty_page(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [], 'links' => ['next' => null]], 200),
        ]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $spaces = $resource->all();

        $this->assertInstanceOf(SpaceCollection::class, $spaces);
        $this->assertCount(0, $spaces);
    }
}
