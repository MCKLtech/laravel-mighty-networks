<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Event;
use MCKLtech\MightyNetworks\Enums\EventFrequency;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 77,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'post_type' => 'event',
            'creator' => ['id' => 9, 'name' => 'Host'],
            'images' => ['https://cdn.mn.co/events/77.jpg'],
            'permalink' => 'https://example.mn.co/posts/77',
            'title' => 'Monthly Community Meetup',
            'description' => 'Join us',
            'recurrence_rule' => 'FREQ=WEEKLY',
            'rsvp_enabled' => true,
            'rsvp_closed' => false,
            'restricted_event' => false,
            'post_in_feed' => true,
            'event_type' => 'online_meeting',
            'starts_at' => '2024-05-01T18:00:00+00:00',
            'ends_at' => '2024-05-01T19:00:00+00:00',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'Zoom',
            'link' => 'https://zoom.us/j/123',
            'frequency' => 'weekly',
            'interval' => 2,
        ], $overrides);
    }

    public function test_it_maps_every_documented_field(): void
    {
        $event = Event::fromArray($this->payload());

        $this->assertSame(77, $event->id);
        $this->assertSame('event', $event->postType);
        $this->assertSame(['id' => 9, 'name' => 'Host'], $event->creator);
        $this->assertSame(['https://cdn.mn.co/events/77.jpg'], $event->images);
        $this->assertSame('https://example.mn.co/posts/77', $event->permalink);
        $this->assertSame('Monthly Community Meetup', $event->title);
        $this->assertSame('Join us', $event->description);
        $this->assertSame('FREQ=WEEKLY', $event->recurrenceRule);
        $this->assertTrue($event->rsvpEnabled);
        $this->assertFalse($event->rsvpClosed);
        $this->assertFalse($event->restrictedEvent);
        $this->assertTrue($event->postInFeed);
        $this->assertSame('online_meeting', $event->eventType);
        $this->assertSame('America/Los_Angeles', $event->timeZone);
        $this->assertSame('Zoom', $event->location);
        $this->assertSame('https://zoom.us/j/123', $event->link);
        $this->assertSame(EventFrequency::Weekly, $event->frequency);
        $this->assertSame(2, $event->interval);
    }

    public function test_it_parses_dates_as_immutable_carbon_instances(): void
    {
        $event = Event::fromArray($this->payload());

        $this->assertInstanceOf(CarbonImmutable::class, $event->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $event->createdAt->toIso8601String());
        $this->assertInstanceOf(CarbonImmutable::class, $event->startsAt);
        $this->assertSame('2024-05-01T18:00:00+00:00', $event->startsAt->toIso8601String());
        $this->assertSame('2024-05-01T19:00:00+00:00', $event->endsAt?->toIso8601String());
    }

    public function test_it_treats_missing_optional_fields_as_null(): void
    {
        $event = Event::fromArray([
            'id' => 1,
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-01T00:00:00+00:00',
            'post_type' => 'event',
            'creator' => [],
            'permalink' => 'https://example.mn.co/posts/1',
            'title' => 'Standalone',
            'event_type' => 'local_meetup',
        ]);

        $this->assertNull($event->description);
        $this->assertNull($event->startsAt);
        $this->assertNull($event->endsAt);
        $this->assertNull($event->frequency);
        $this->assertNull($event->interval);
        $this->assertSame([], $event->images);
        $this->assertSame([], $event->creator);
    }

    public function test_it_ignores_an_unrecognised_frequency_value(): void
    {
        $event = Event::fromArray($this->payload(['frequency' => 'fortnightly']));

        $this->assertNull($event->frequency);
    }

    public function test_it_keeps_only_string_image_urls(): void
    {
        $event = Event::fromArray($this->payload(['images' => ['https://a.jpg', 5, null, 'https://b.jpg']]));

        $this->assertSame(['https://a.jpg', 'https://b.jpg'], $event->images);
    }
}
