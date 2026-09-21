<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;
use PHPUnit\Framework\TestCase;

final class RsvpTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id' => 5,
            'updated' => '2024-05-01T18:05:00+00:00',
            'status' => 'maybe',
            'event' => ['id' => 77, 'title' => 'Meetup'],
            'member' => ['id' => 42, 'first_name' => 'Jane'],
            'event_instance' => [
                'post_id' => 77,
                'starts_at' => '2024-05-01T18:00:00+00:00',
                'ends_at' => '2024-05-01T19:00:00+00:00',
                'instance_index' => 0,
            ],
        ], $overrides);
    }

    public function test_it_maps_every_documented_field(): void
    {
        $rsvp = Rsvp::fromArray($this->payload());

        $this->assertSame(5, $rsvp->id);
        $this->assertSame(RsvpStatus::Maybe, $rsvp->status);
        $this->assertSame(['id' => 77, 'title' => 'Meetup'], $rsvp->event);
        $this->assertSame(['id' => 42, 'first_name' => 'Jane'], $rsvp->member);
        $this->assertSame(77, $rsvp->eventInstance['post_id'] ?? null);
    }

    public function test_it_parses_the_updated_timestamp_as_immutable_carbon(): void
    {
        $rsvp = Rsvp::fromArray($this->payload());

        $this->assertInstanceOf(CarbonImmutable::class, $rsvp->updatedAt);
        $this->assertSame('2024-05-01T18:05:00+00:00', $rsvp->updatedAt->toIso8601String());
    }

    public function test_it_leaves_event_instance_null_when_omitted(): void
    {
        $rsvp = Rsvp::fromArray($this->payload(['event_instance' => null]));

        $this->assertNull($rsvp->eventInstance);
    }

    public function test_it_accepts_each_documented_status(): void
    {
        $this->assertSame(RsvpStatus::Yes, Rsvp::fromArray($this->payload(['status' => 'yes']))->status);
        $this->assertSame(RsvpStatus::No, Rsvp::fromArray($this->payload(['status' => 'no']))->status);
    }
}
