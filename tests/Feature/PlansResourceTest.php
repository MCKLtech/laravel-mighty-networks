<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\InviteCollection;
use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\Collections\PlanCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\Plan;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Enums\PlanStatus;
use MCKLtech\MightyNetworks\Enums\PricingType;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\AddPlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\CreatePlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\DeletePlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\DeletePlanRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlanInvitesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlanMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlansRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\RemovePlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ResendPlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\UpdatePlanInviteRequest;
use MCKLtech\MightyNetworks\Resources\PlansResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class PlansResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Founders Circle',
            'description' => 'High-touch membership',
            'status' => 'visible',
            'pricing_type' => 'subscription',
            'visible_to_members' => true,
            'external' => false,
            'multiple' => true,
            'permalink' => 'https://example.mn.co/plans/founders',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invitePayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 11,
            'created_at' => '2024-02-01T09:00:00+00:00',
            'updated_at' => '2024-02-02T09:30:00+00:00',
            'recipient_email' => 'claude@example.com',
            'recipient_first_name' => 'Claude',
            'recipient_last_name' => 'Monet',
            'sender_id' => 99,
            'user_id' => 5,
        ], $overrides);
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

    public function test_all_returns_a_plan_collection_and_asserts_the_request(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->planPayload(['id' => 1]), $this->planPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $plans = $resource->all(perPage: 50);

        $this->assertInstanceOf(PlanCollection::class, $plans);
        $this->assertCount(2, $plans);
        $this->assertSame([1, 2], $plans->map(static fn (Plan $plan): int => $plan->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPlansRequest
                && $request->resolveEndpoint() === 'networks/12345/plans'
                && $request->getMethod() === Method::GET
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_find_by_id_returns_a_typed_plan_and_uses_the_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->planPayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $plan = $resource->findById(7);

        $this->assertInstanceOf(Plan::class, $plan);
        $this->assertSame(7, $plan->id);
        $this->assertSame(PlanStatus::Visible, $plan->status);
        $this->assertSame(PricingType::Subscription, $plan->pricingType);
        $this->assertTrue($plan->multiple);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPlanRequest
                && $request->resolveEndpoint() === 'networks/12345/plans/7/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Plan not found'], 404)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(7));
    }

    public function test_delete_sends_a_delete_to_the_plan_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $resource->delete(7);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePlanRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/plans/7/';
        });
    }

    public function test_members_returns_a_member_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->memberPayload()],
                'meta' => ['current_page' => 1, 'total_pages' => 1],
            ], 200),
        ]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $members = $resource->members(7);

        $this->assertInstanceOf(MemberCollection::class, $members);
        $this->assertSame(42, $members->first()?->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPlanMembersRequest
                && $request->resolveEndpoint() === 'networks/12345/plans/7/members'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_add_member_posts_the_user_id_as_a_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->planPayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $plan = $resource->addMember(7, 42);

        $this->assertSame(7, $plan->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof AddPlanMemberRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/plans/7/members'
                && $request->query()->get('user_id') === 42;
        });
    }

    public function test_member_fetches_a_single_plan_member(): void
    {
        $mock = new MockClient([MockResponse::make($this->memberPayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $member = $resource->member(7, 42);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame(42, $member->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPlanMemberRequest
                && $request->resolveEndpoint() === 'networks/12345/plans/7/members/42/';
        });
    }

    public function test_member_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $this->assertNull($resource->memberOrNull(7, 42));
    }

    public function test_remove_member_sends_a_delete(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $resource->removeMember(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof RemovePlanMemberRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/plans/7/members/42/';
        });
    }

    public function test_invites_returns_an_invite_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->invitePayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $invites = $resource->invites(7);

        $this->assertInstanceOf(InviteCollection::class, $invites);
        $this->assertSame(11, $invites->first()?->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPlanInvitesRequest
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites';
        });
    }

    public function test_create_invite_posts_query_parameters(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $invite = $resource->createInvite(
            planId: 7,
            email: 'claude@example.com',
            message: 'Join us',
            couponId: 3,
        );

        $this->assertSame(11, $invite->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreatePlanInviteRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites'
                && $request->query()->get('email') === 'claude@example.com'
                && $request->query()->get('message') === 'Join us'
                && $request->query()->get('coupon_id') === 3
                && $request->query()->get('user_id') === null;
        });
    }

    public function test_invite_fetches_a_single_plan_invite(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $invite = $resource->invite(7, 11);

        $this->assertInstanceOf(Invite::class, $invite);
        $this->assertSame(11, $invite->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPlanInviteRequest
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites/11/';
        });
    }

    public function test_invite_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $this->assertNull($resource->inviteOrNull(7, 11));
    }

    public function test_update_invite_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $resource->updateInvite(7, 11, new UpdateInviteData(recipientLastName: 'Monet'));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof UpdatePlanInviteRequest) {
                return false;
            }

            return $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites/11/'
                && $request->body()->all() === ['recipient_last_name' => 'Monet'];
        });
    }

    public function test_resend_invite_sends_a_put(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $resource->resendInvite(7, 11);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ResendPlanInviteRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites/11/';
        });
    }

    public function test_delete_invite_sends_a_delete(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $resource->deleteInvite(7, 11);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePlanInviteRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/plans/7/invites/11/';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Plan not found'], 404)]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(7);
    }

    public function test_it_paginates_plans_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->planPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->planPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 10)->items() as $plan) {
            $this->assertInstanceOf(Plan::class, $plan);
            $ids[] = $plan->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_plan(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->planPayload(['id' => 1])], 'links' => ['next' => null]], 200),
        ]);

        $resource = new PlansResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Plan $plan) use (&$ids): void {
            $ids[] = $plan->id;
        });

        $this->assertSame([1], $ids);
    }
}
