<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\WebhookEventType;
use PHPUnit\Framework\TestCase;

final class WebhookEventTypeTest extends TestCase
{
    public function test_it_has_all_forty_seven_sdl_values(): void
    {
        $this->assertCount(47, WebhookEventType::cases());

        $this->assertSame('POST_CREATED', WebhookEventType::PostCreated->value);
        $this->assertSame('MEMBER_JOINED', WebhookEventType::MemberJoined->value);
        $this->assertSame('RSVP_UPDATED', WebhookEventType::RsvpUpdated->value);
        $this->assertSame('REPORTED_CONTENT_CREATED', WebhookEventType::ReportedContentCreated->value);
    }

    public function test_event_names_are_defined_only_once(): void
    {
        $values = array_map(
            static fn (WebhookEventType $type): string => $type->value,
            WebhookEventType::cases(),
        );

        $this->assertSame($values, array_values(array_unique($values)));
    }

    public function test_from_event_name_accepts_upper_snake(): void
    {
        $this->assertSame(WebhookEventType::PostCreated, WebhookEventType::fromEventName('POST_CREATED'));
    }

    public function test_from_event_name_normalises_pascal_case(): void
    {
        $this->assertSame(WebhookEventType::PostCreated, WebhookEventType::fromEventName('PostCreated'));
        $this->assertSame(
            WebhookEventType::MemberCourseProgressCompleted,
            WebhookEventType::fromEventName('MemberCourseProgressCompleted'),
        );
    }

    public function test_from_event_name_strips_the_graphql_hook_suffix(): void
    {
        $this->assertSame(WebhookEventType::MemberJoined, WebhookEventType::fromEventName('MemberJoinedHook'));
        $this->assertSame(
            WebhookEventType::MemberSubscriptionCanceled,
            WebhookEventType::fromEventName('MEMBER_SUBSCRIPTION_CANCELED'),
        );
    }

    public function test_from_event_name_returns_null_for_unknown_or_empty_names(): void
    {
        $this->assertNull(WebhookEventType::fromEventName('TotallyNewEvent'));
        $this->assertNull(WebhookEventType::fromEventName(''));
        $this->assertNull(WebhookEventType::fromEventName('   '));
    }

    public function test_to_friendly_produces_a_readable_label(): void
    {
        $this->assertSame('Post Created', WebhookEventType::PostCreated->toFriendly());
        $this->assertSame('Rsvp Updated', WebhookEventType::RsvpUpdated->toFriendly());
    }
}
