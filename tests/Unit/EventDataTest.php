<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\NewEventData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateEventData;
use MCKLtech\MightyNetworks\Enums\EventFrequency;
use PHPUnit\Framework\TestCase;

final class EventDataTest extends TestCase
{
    public function test_new_event_data_emits_snake_case_and_omits_nulls(): void
    {
        $data = new NewEventData(
            title: 'Monthly Meetup',
            startsAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
            endsAt: CarbonImmutable::parse('2024-05-01T19:00:00+00:00'),
            eventType: 'online_meeting',
            spaceId: 123,
            description: 'Join us',
            frequency: EventFrequency::Weekly,
            interval: 2,
            byDays: 'MO,WE,FR',
        );

        $this->assertSame([
            'title' => 'Monthly Meetup',
            'description' => 'Join us',
            'starts_at' => '2024-05-01T18:00:00+00:00',
            'ends_at' => '2024-05-01T19:00:00+00:00',
            'event_type' => 'online_meeting',
            'space_id' => 123,
            'frequency' => 'weekly',
            'interval' => 2,
            'by_days' => 'MO,WE,FR',
        ], $data->toArray());
    }

    public function test_new_event_data_accepts_a_raw_frequency_string_and_bools(): void
    {
        $data = new NewEventData(
            title: 'Local',
            startsAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
            endsAt: CarbonImmutable::parse('2024-05-01T19:00:00+00:00'),
            eventType: 'local_meetup',
            spaceId: 1,
            frequency: 'monthly',
            rsvpClosed: true,
            postInFeed: false,
        );

        $this->assertSame('monthly', $data->toArray()['frequency']);
        $this->assertTrue($data->toArray()['rsvp_closed']);
        $this->assertFalse($data->toArray()['post_in_feed']);
    }

    public function test_update_event_data_omits_null_fields(): void
    {
        $data = new UpdateEventData(
            title: 'Renamed',
            startsAt: CarbonImmutable::parse('2024-06-01T18:00:00+00:00'),
        );

        $this->assertSame([
            'title' => 'Renamed',
            'starts_at' => '2024-06-01T18:00:00+00:00',
        ], $data->toArray());
    }

    public function test_update_event_data_can_be_empty(): void
    {
        $this->assertSame([], (new UpdateEventData)->toArray());
    }
}
