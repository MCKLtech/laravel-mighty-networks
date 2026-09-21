<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCollectionGroupData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateSpaceData;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ReplaceCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceSpaceRequest;
use MCKLtech\MightyNetworks\Resources\CollectionsResource;
use MCKLtech\MightyNetworks\Resources\SpacesResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class ReplaceOperationsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function spacePayload(): array
    {
        return [
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Announcements',
            'collection_id' => 3,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionPayload(): array
    {
        return [
            'id' => 9,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Getting Started',
            'visible_to_members' => true,
            'position' => 1,
            'explorable' => true,
            'description' => 'Start here',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseworkPayload(): array
    {
        return [
            'id' => 100,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'space_id' => 7,
            'type' => 'lesson',
            'title' => 'Welcome',
            'status' => 'posted',
            'position' => 1,
            'completion_criteria' => 'visited',
            'unlocking_criteria' => 'sequential',
            'children_count' => 0,
            'permalink' => 'https://example.mn.co/courses/7/lessons/100',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(): array
    {
        return [
            'id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'email' => 'jane@example.com',
            'member_type' => 'full',
            'permalink' => 'https://example.mn.co/members/42',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ];
    }

    public function test_replace_space_sends_a_put_without_a_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->spacePayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $space = $resource->replace(7, new UpdateSpaceData(name: 'Renamed', collectionId: 4));

        $this->assertSame(7, $space->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceSpaceRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/spaces/7'
                && $request->body()->all() === ['name' => 'Renamed', 'collection_id' => 4];
        });
    }

    public function test_replace_collection_sends_a_put_with_a_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->collectionPayload(), 200)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $collection = $resource->replace(9, new UpdateCollectionGroupData(name: 'Renamed', visibleToMembers: false));

        $this->assertSame(9, $collection->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceCollectionGroupRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/collections/9/'
                && $request->body()->all() === ['name' => 'Renamed', 'visible_to_members' => false];
        });
    }

    public function test_replace_coursework_sends_a_put_with_a_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->courseworkPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $coursework = $resource->replaceCoursework(7, 100, new UpdateCourseworkData(title: 'Renamed'));

        $this->assertSame(100, $coursework->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceCourseworkRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/courseworks/100/'
                && $request->body()->all() === ['title' => 'Renamed'];
        });
    }

    public function test_replace_space_member_sends_a_put_with_a_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new SpacesResource($this->admin($mock), '12345');

        $member = $resource->replaceMember(7, 42, new UpdateMemberData(role: MembershipRole::Moderator));

        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceSpaceMemberRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/spaces/7/members/42/'
                && $request->body()->all() === ['role' => 'moderator'];
        });
    }
}
