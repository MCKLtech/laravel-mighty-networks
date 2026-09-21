<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;

/**
 * Request payload for creating an RSVP.
 *
 * `memberId` and `status` are required by the API. `instanceAt` targets a
 * specific instance of a recurring event.
 */
final readonly class NewRsvpData
{
    public function __construct(
        public int $memberId,
        public RsvpStatus|string $status,
        public ?CarbonImmutable $instanceAt = null,
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
                'member_id' => $this->memberId,
                'status' => $this->status instanceof RsvpStatus ? $this->status->value : $this->status,
                'instance_at' => $this->instanceAt?->toIso8601String(),
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
