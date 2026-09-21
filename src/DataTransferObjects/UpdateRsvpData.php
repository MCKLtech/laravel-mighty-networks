<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;

/**
 * Request payload for updating an RSVP.
 *
 * Every field is optional so a partial `PATCH` only sends what changed.
 */
final readonly class UpdateRsvpData
{
    public function __construct(
        public ?int $memberId = null,
        public RsvpStatus|string|null $status = null,
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
