<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMutationPayload;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLWebhookCallback;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\BanMemberInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateCommentInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateMemberInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreatePaymentPlanMembershipInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateReactionInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateRsvpInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateSpaceInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateSpaceMembershipsInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateWebhookCallbackInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\DeleteCommentInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\DeleteMemberInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\DeletePostInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\SpaceFeatureToggleInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\UpdateMemberInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\UpdateSpaceInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\UpdateWebhookCallbackInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\AbstractTypedMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\BanMemberMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateCommentMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateEventMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateInvitesMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateMemberMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreatePaymentPlanMembershipMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreatePostMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateReactionMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateRsvpMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateSpaceMembershipsMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateSpaceMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateWebhookCallbackMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\DeleteCommentMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\DeleteMemberMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\DeletePostMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\UpdateEventMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\UpdateMemberMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\UpdatePostMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\UpdateSpaceMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\UpdateWebhookCallbackMutation;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class GraphQLTypedMutationsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function respondWith(GraphQLMutation $operation, array $payload): MockClient
    {
        return new MockClient([
            MockResponse::make(['data' => [$operation->value => $payload]], 200),
        ]);
    }

    /**
     * Assert the request builds the expected operation envelope.
     */
    private function assertEnvelope(AbstractTypedMutation $request, GraphQLMutation $operation): void
    {
        $this->assertSame($operation, $request->operation());
        $this->assertStringStartsWith(
            sprintf('mutation %s($input: %s!) { %s(input: $input) {', $operation->operationName(), $operation->inputType(), $operation->value),
            $request->document(),
        );
    }

    public function test_create_member_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateMember, [
            'clientMutationId' => 'c1', 'errors' => [],
            'member' => ['id' => 'gid://1', 'resourceId' => '1', 'name' => 'Jane'],
        ]);

        $request = new CreateMemberMutation('12345', new CreateMemberInput(
            email: 'jane@example.com', firstName: 'Jane', lastName: 'Doe', role: 'HOST',
        ));

        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateMember);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertTrue($dto->succeeded());
        $this->assertSame('Jane', $dto->member()?->name);
        $this->assertSame(
            ['email' => 'jane@example.com', 'firstName' => 'Jane', 'lastName' => 'Doe', 'role' => 'HOST'],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_update_member_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::UpdateMember, [
            'clientMutationId' => null, 'errors' => [],
            'member' => ['id' => 'gid://1', 'resourceId' => '1', 'shortBio' => 'Hi'],
        ]);

        $request = new UpdateMemberMutation('12345', new UpdateMemberInput(id: 'gid://1', shortBio: 'Hi'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::UpdateMember);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Hi', $dto->member()?->shortBio);
        $this->assertSame(['id' => 'gid://1', 'shortBio' => 'Hi'], $request->body()->all()['variables']['input']);
    }

    public function test_delete_member_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::DeleteMember, [
            'clientMutationId' => null, 'errors' => [],
            'member' => ['id' => 'gid://1', 'resourceId' => '1'],
        ]);

        $request = new DeleteMemberMutation('12345', new DeleteMemberInput(id: 'gid://1'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::DeleteMember);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame(['id' => 'gid://1'], $request->body()->all()['variables']['input']);
    }

    public function test_ban_member_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::BanMember, [
            'clientMutationId' => null, 'errors' => [],
            'member' => ['id' => 'gid://1', 'resourceId' => '1'],
        ]);

        $request = new BanMemberMutation('12345', new BanMemberInput(memberId: 'gid://1', reason: 'spam'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::BanMember);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame(['memberId' => 'gid://1', 'reason' => 'spam'], $request->body()->all()['variables']['input']);
    }

    public function test_create_post_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreatePost, [
            'clientMutationId' => null, 'errors' => [],
            'post' => ['id' => 'gid://post/1', 'title' => 'Hello'],
        ]);

        $request = new CreatePostMutation('12345', ['spaceId' => 'gid://space/1', 'title' => 'Hello']);
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreatePost);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Hello', $dto->entity('post')['title'] ?? null);
        $this->assertSame(['spaceId' => 'gid://space/1', 'title' => 'Hello'], $request->body()->all()['variables']['input']);
    }

    public function test_update_post_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::UpdatePost, [
            'clientMutationId' => null, 'errors' => [],
            'post' => ['id' => 'gid://post/1', 'title' => 'Edited'],
        ]);

        $request = new UpdatePostMutation('12345', ['id' => 'gid://post/1', 'title' => 'Edited']);
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::UpdatePost);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame(['id' => 'gid://post/1', 'title' => 'Edited'], $request->body()->all()['variables']['input']);
    }

    public function test_delete_post_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::DeletePost, [
            'clientMutationId' => null, 'errors' => [], 'deletedId' => 'gid://post/1',
        ]);

        $request = new DeletePostMutation('12345', new DeletePostInput(id: 'gid://post/1'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::DeletePost);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('gid://post/1', $dto->deletedId());
        $this->assertStringContainsString('deletedId', $request->document());
    }

    public function test_create_comment_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateComment, [
            'clientMutationId' => null, 'errors' => [],
            'comment' => ['id' => 'gid://comment/1', 'bodyText' => 'Nice'],
        ]);

        $request = new CreateCommentMutation('12345', new CreateCommentInput(body: 'Nice', postId: 'gid://post/1'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateComment);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Nice', $dto->entity('comment')['bodyText'] ?? null);
        $this->assertSame(['body' => 'Nice', 'postId' => 'gid://post/1'], $request->body()->all()['variables']['input']);
    }

    public function test_delete_comment_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::DeleteComment, [
            'clientMutationId' => null, 'errors' => [], 'deletedId' => 'gid://comment/1',
        ]);

        $request = new DeleteCommentMutation('12345', new DeleteCommentInput(id: 'gid://comment/1'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::DeleteComment);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('gid://comment/1', $dto->deletedId());
    }

    public function test_create_reaction_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateReaction, [
            'clientMutationId' => null, 'errors' => [],
            'reaction' => ['id' => 'gid://reaction/1', 'resourceId' => '1', 'emoji' => '👍', 'baseEmoji' => '👍'],
        ]);

        $request = new CreateReactionMutation('12345', new CreateReactionInput(emoji: '👍', targetId: 'gid://post/1'));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateReaction);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('👍', $dto->reaction()?->emoji);
        $this->assertSame(['emoji' => '👍', 'targetId' => 'gid://post/1'], $request->body()->all()['variables']['input']);
    }

    public function test_create_space_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateSpace, [
            'clientMutationId' => null, 'errors' => [],
            'space' => ['id' => 'gid://space/1', 'title' => 'New'],
            'collectionNewlyCreated' => true,
            'collection' => ['id' => 'gid://collection/1', 'name' => 'General'],
        ]);

        $request = new CreateSpaceMutation('12345', new CreateSpaceInput(
            templateCanonicalName: 'course', title: 'New', collectionName: 'General',
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateSpace);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('New', $dto->entity('space')['title'] ?? null);
        $this->assertSame('General', $dto->spacesCollection()?->name);
        $this->assertSame(
            ['templateCanonicalName' => 'course', 'title' => 'New', 'collectionName' => 'General'],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_update_space_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::UpdateSpace, [
            'clientMutationId' => null, 'errors' => [],
            'space' => ['id' => 'gid://space/1', 'title' => 'Renamed'],
        ]);

        $request = new UpdateSpaceMutation('12345', new UpdateSpaceInput(
            spaceId: 'gid://space/1',
            title: 'Renamed',
            featureToggles: [new SpaceFeatureToggleInput(key: 'chat', enabled: true)],
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::UpdateSpace);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Renamed', $dto->entity('space')['title'] ?? null);
        $this->assertSame([
            'spaceId' => 'gid://space/1',
            'title' => 'Renamed',
            'featureToggles' => [['key' => 'chat', 'enabled' => true]],
        ], $request->body()->all()['variables']['input']);
    }

    public function test_create_event_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateEvent, [
            'clientMutationId' => null, 'errors' => [],
            'event' => ['id' => 'gid://event/1', 'title' => 'Meetup'],
        ]);

        $request = new CreateEventMutation('12345', [
            'spaceId' => 'gid://space/1', 'title' => 'Meetup', 'eventType' => 'IN_PERSON',
            'startsAt' => '2026-01-01T10:00:00+00:00', 'endsAt' => '2026-01-01T11:00:00+00:00',
        ]);
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateEvent);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Meetup', $dto->entity('event')['title'] ?? null);
        $this->assertSame('gid://space/1', $request->body()->all()['variables']['input']['spaceId']);
    }

    public function test_update_event_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::UpdateEvent, [
            'clientMutationId' => null, 'errors' => [],
            'event' => ['id' => 'gid://event/1', 'title' => 'Moved'],
        ]);

        $request = new UpdateEventMutation('12345', ['id' => 'gid://event/1', 'title' => 'Moved']);
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::UpdateEvent);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('Moved', $dto->entity('event')['title'] ?? null);
    }

    public function test_create_rsvp_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateRsvp, [
            'clientMutationId' => null, 'errors' => [],
            'rsvp' => ['id' => 'gid://rsvp/1', 'resourceId' => '1', 'status' => 'GOING'],
        ]);

        $request = new CreateRsvpMutation('12345', new CreateRsvpInput(
            eventId: 'gid://event/1', status: 'GOING', instanceAt: CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateRsvp);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('GOING', $dto->rsvp()?->status);
        $this->assertSame('2026-01-01T10:00:00+00:00', $request->body()->all()['variables']['input']['instanceAt']);
    }

    public function test_create_invites_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateInvites, [
            'clientMutationId' => null, 'errors' => [], 'count' => 2, 'mode' => 'EMAIL', 'ignoredRecipients' => [],
        ]);

        $request = new CreateInvitesMutation('12345', [
            'recipients' => [['email' => 'a@example.com', 'firstName' => 'A']],
            'message' => 'Join us',
        ]);
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateInvites);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame(2, $dto->count());
        $this->assertSame('EMAIL', $dto->mode());
        $this->assertStringContainsString('ignoredRecipients', $request->document());
    }

    public function test_create_payment_plan_membership_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreatePaymentPlanMembership, [
            'clientMutationId' => null, 'errors' => [], 'outcome' => 'CREATED',
            'member' => ['id' => 'gid://member/1', 'resourceId' => '1'],
            'paymentPlan' => ['id' => 'gid://plan/1', 'name' => 'Gold'],
            'paymentSubscription' => ['id' => 'gid://sub/1', 'status' => 'ACTIVE'],
        ]);

        $request = new CreatePaymentPlanMembershipMutation('12345', new CreatePaymentPlanMembershipInput(
            memberId: 'gid://member/1', planId: 'gid://plan/1',
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreatePaymentPlanMembership);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('CREATED', $dto->outcome());
        $this->assertSame('Gold', $dto->entity('paymentPlan')['name'] ?? null);
        $this->assertSame(
            ['memberId' => 'gid://member/1', 'planId' => 'gid://plan/1'],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_create_space_memberships_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateSpaceMemberships, [
            'clientMutationId' => null, 'errors' => [], 'outcome' => 'ADDED',
            'member' => ['id' => 'gid://member/1', 'resourceId' => '1'],
            'spaces' => [['id' => 'gid://space/1', 'title' => 'A'], ['id' => 'gid://space/2', 'title' => 'B']],
        ]);

        $request = new CreateSpaceMembershipsMutation('12345', new CreateSpaceMembershipsInput(
            memberId: 'gid://member/1', spaceIds: ['gid://space/1', 'gid://space/2'],
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateSpaceMemberships);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertSame('ADDED', $dto->outcome());
        $this->assertCount(2, $dto->entity('spaces') ?? []);
        $this->assertSame(
            ['memberId' => 'gid://member/1', 'spaceIds' => ['gid://space/1', 'gid://space/2']],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_create_webhook_callback_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateWebhookCallback, [
            'clientMutationId' => null, 'errors' => [],
            'webhookCallback' => [
                'id' => 'gid://webhook/1', 'resourceId' => '1', 'url' => 'https://example.com/hook',
                'includedEvents' => ['POST_CREATED'], 'disabled' => false,
            ],
        ]);

        $request = new CreateWebhookCallbackMutation('12345', new CreateWebhookCallbackInput(
            url: 'https://example.com/hook', includedEvents: [WebhookEventType::PostCreated],
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::CreateWebhookCallback);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $callback = $dto->webhookCallback();
        $this->assertInstanceOf(GraphQLWebhookCallback::class, $callback);
        $this->assertSame('https://example.com/hook', $callback->url);
        $this->assertSame([WebhookEventType::PostCreated], $callback->includedEvents);
        $this->assertSame(
            ['url' => 'https://example.com/hook', 'includedEvents' => ['POST_CREATED']],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_update_webhook_callback_mutation(): void
    {
        $mock = $this->respondWith(GraphQLMutation::UpdateWebhookCallback, [
            'clientMutationId' => null, 'errors' => [],
            'webhookCallback' => [
                'id' => 'gid://webhook/1', 'resourceId' => '1', 'url' => 'https://example.com/v2',
                'includedEvents' => ['MEMBER_JOINED'], 'disabled' => true,
            ],
        ]);

        $request = new UpdateWebhookCallbackMutation('12345', new UpdateWebhookCallbackInput(
            id: 'gid://webhook/1', url: 'https://example.com/v2', includedEvents: [WebhookEventType::MemberJoined],
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertEnvelope($request, GraphQLMutation::UpdateWebhookCallback);
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertTrue($dto->webhookCallback()?->disabled);
        $this->assertSame(
            ['id' => 'gid://webhook/1', 'url' => 'https://example.com/v2', 'includedEvents' => ['MEMBER_JOINED']],
            $request->body()->all()['variables']['input'],
        );
    }

    public function test_failed_mutation_payload_surfaces_errors_without_throwing(): void
    {
        $mock = $this->respondWith(GraphQLMutation::CreateMember, [
            'clientMutationId' => null, 'errors' => ['Email is already taken.'], 'member' => null,
        ]);

        $request = new CreateMemberMutation('12345', new CreateMemberInput(
            email: 'jane@example.com', firstName: 'Jane', lastName: 'Doe',
        ));
        $dto = $this->graphql($mock)->send($request)->dto();

        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertFalse($dto->succeeded());
        $this->assertSame('Email is already taken.', $dto->firstError());
    }
}
