<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateMemberInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateRsvpInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateSpaceInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateSpaceMembershipsInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateWebhookCallbackInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\SpaceFeatureToggleInput;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\UpdateSpaceInput;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;
use PHPUnit\Framework\TestCase;

final class GraphQLMutationInputsTest extends TestCase
{
    public function test_it_emits_camel_case_keys_and_omits_nulls(): void
    {
        $input = new CreateMemberInput(
            email: 'jane@example.com',
            firstName: 'Jane',
            lastName: 'Doe',
            role: 'MODERATOR',
        );

        $this->assertSame([
            'email' => 'jane@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'role' => 'MODERATOR',
        ], $input->toArray());
    }

    public function test_it_maps_nested_inputs_and_events(): void
    {
        $space = new UpdateSpaceInput(
            spaceId: 'gid://space/1',
            featureToggles: [new SpaceFeatureToggleInput(key: 'chat', enabled: false)],
        );

        $this->assertSame([
            'spaceId' => 'gid://space/1',
            'featureToggles' => [['key' => 'chat', 'enabled' => false]],
        ], $space->toArray());

        $webhook = new CreateWebhookCallbackInput(
            url: 'https://example.com/hook',
            includedEvents: [WebhookEventType::PostCreated, 'MEMBER_JOINED'],
        );

        $this->assertSame([
            'url' => 'https://example.com/hook',
            'includedEvents' => ['POST_CREATED', 'MEMBER_JOINED'],
        ], $webhook->toArray());
    }

    public function test_it_renders_dates_as_iso8601_and_keeps_lists(): void
    {
        $rsvp = new CreateRsvpInput(
            eventId: 'gid://event/1',
            status: 'GOING',
            instanceAt: CarbonImmutable::parse('2026-01-01T10:00:00+00:00'),
        );

        $this->assertSame([
            'eventId' => 'gid://event/1',
            'status' => 'GOING',
            'instanceAt' => '2026-01-01T10:00:00+00:00',
        ], $rsvp->toArray());

        $memberships = new CreateSpaceMembershipsInput(memberId: 'm1', spaceIds: ['s1', 's2']);
        $this->assertSame(['memberId' => 'm1', 'spaceIds' => ['s1', 's2']], $memberships->toArray());

        $space = new CreateSpaceInput(templateCanonicalName: 'course', title: 'New', enabledFeatureKeys: ['chat']);
        $this->assertSame([
            'templateCanonicalName' => 'course',
            'title' => 'New',
            'enabledFeatureKeys' => ['chat'],
        ], $space->toArray());
    }
}
