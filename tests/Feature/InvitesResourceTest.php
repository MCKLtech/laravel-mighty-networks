<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\InviteCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\NewInviteData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\CreateInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\DeleteInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\ListInvitesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\ReplaceInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\UpdateInviteRequest;
use MCKLtech\MightyNetworks\Resources\InvitesResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class InvitesResourceTest extends TestCase
{
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

    public function test_all_returns_an_invite_collection_and_filters_by_email(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->invitePayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $invites = $resource->all(email: 'claude@example.com', perPage: 10);

        $this->assertInstanceOf(InviteCollection::class, $invites);

        $invite = $invites->first();
        $this->assertInstanceOf(Invite::class, $invite);
        $this->assertSame(11, $invite->id);
        $this->assertSame(99, $invite->senderId);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListInvitesRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/invites'
                && $request->query()->get('email') === 'claude@example.com'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_create_maps_the_dto_to_a_snake_case_body_and_omits_nulls(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $invite = $resource->create(new NewInviteData(
            recipientEmail: 'claude@example.com',
            recipientFirstName: 'Claude',
        ));

        $this->assertInstanceOf(Invite::class, $invite);
        $this->assertSame(11, $invite->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateInviteRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/invites'
                && $request->body()->all() === [
                    'recipient_email' => 'claude@example.com',
                    'recipient_first_name' => 'Claude',
                ];
        });
    }

    public function test_update_sends_a_patch_with_only_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $resource->update(11, new UpdateInviteData(recipientLastName: 'Monet'));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof UpdateInviteRequest) {
                return false;
            }

            return $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/invites/11/'
                && $request->body()->all() === ['recipient_last_name' => 'Monet'];
        });
    }

    public function test_replace_sends_a_put_with_the_full_payload(): void
    {
        $mock = new MockClient([MockResponse::make($this->invitePayload(), 200)]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $resource->replace(11, new NewInviteData(
            recipientEmail: 'new@example.com',
            userId: 5,
        ));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof ReplaceInviteRequest) {
                return false;
            }

            return $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/invites/11/'
                && $request->body()->all() === [
                    'recipient_email' => 'new@example.com',
                    'user_id' => 5,
                ];
        });
    }

    public function test_delete_sends_a_delete_to_the_invite_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $resource->delete(11);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteInviteRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/invites/11/';
        });
    }

    public function test_it_paginates_invites_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->invitePayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2],
            ], 200),
            MockResponse::make([
                'data' => [$this->invitePayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2],
            ], 200),
        ]);

        $resource = new InvitesResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 1)->items() as $invite) {
            $this->assertInstanceOf(Invite::class, $invite);
            $ids[] = $invite->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }
}
