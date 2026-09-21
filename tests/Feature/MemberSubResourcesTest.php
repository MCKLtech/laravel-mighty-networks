<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\PlanCollection;
use MCKLtech\MightyNetworks\Collections\SpaceCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Plan;
use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ListMemberPlansRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ListMemberSpacesRequest;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class MemberSubResourcesTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function planPayload(): array
    {
        return [
            'id' => 3,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Membership',
            'permalink' => 'https://example.mn.co/plans/3',
            'status' => 'visible',
            'pricing_type' => 'subscription',
        ];
    }

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

    public function test_plans_returns_a_plan_collection_and_asserts_the_path(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->planPayload()],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $plans = $resource->plans(42, perPage: 50);

        $this->assertInstanceOf(PlanCollection::class, $plans);
        $this->assertCount(1, $plans);

        $first = $plans->first();
        $this->assertInstanceOf(Plan::class, $first);
        $this->assertSame(3, $first->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListMemberPlansRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/members/42/plans'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_spaces_returns_a_space_collection_and_asserts_the_path(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->spacePayload()],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new MembersResource($this->admin($mock), '12345');

        $spaces = $resource->spaces(42, perPage: 50);

        $this->assertInstanceOf(SpaceCollection::class, $spaces);
        $this->assertCount(1, $spaces);

        $first = $spaces->first();
        $this->assertInstanceOf(Space::class, $first);
        $this->assertSame(7, $first->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListMemberSpacesRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/members/42/spaces'
                && $request->query()->get('per_page') === 50;
        });
    }
}
