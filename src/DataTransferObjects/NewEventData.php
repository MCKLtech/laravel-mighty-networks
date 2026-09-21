<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\EventFrequency;

/**
 * Request payload for creating an event.
 *
 * `title`, `startsAt`, `endsAt`, `eventType`, and `spaceId` are required by the API.
 */
final readonly class NewEventData
{
    public function __construct(
        public string $title,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public string $eventType,
        public int $spaceId,
        public ?string $description = null,
        public ?string $link = null,
        public ?string $location = null,
        public ?string $timeZone = null,
        public ?bool $rsvpEnabled = null,
        public ?bool $rsvpClosed = null,
        public ?bool $restrictedEvent = null,
        public ?bool $postInFeed = null,
        public EventFrequency|string|null $frequency = null,
        public ?int $interval = null,
        public ?string $byDays = null,
        public ?string $byMonthDays = null,
        public ?int $recurrenceCount = null,
        public ?CarbonImmutable $recurUntil = null,
    ) {}

    /**
     * Convert to the snake_case API payload, omitting null values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'title' => $this->title,
                'description' => $this->description,
                'link' => $this->link,
                'starts_at' => $this->startsAt->toIso8601String(),
                'ends_at' => $this->endsAt->toIso8601String(),
                'event_type' => $this->eventType,
                'space_id' => $this->spaceId,
                'location' => $this->location,
                'time_zone' => $this->timeZone,
                'rsvp_enabled' => $this->rsvpEnabled,
                'rsvp_closed' => $this->rsvpClosed,
                'restricted_event' => $this->restrictedEvent,
                'post_in_feed' => $this->postInFeed,
                'frequency' => $this->frequency instanceof EventFrequency ? $this->frequency->value : $this->frequency,
                'interval' => $this->interval,
                'by_days' => $this->byDays,
                'by_month_days' => $this->byMonthDays,
                'recurrence_count' => $this->recurrenceCount,
                'recur_until' => $this->recurUntil?->toIso8601String(),
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
