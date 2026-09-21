<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

use Carbon\CarbonImmutable;

/**
 * GraphQL `CreateRsvpInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreateRsvpInput
{
    public function __construct(
        public string $eventId,
        public string $status,
        public ?CarbonImmutable $instanceAt = null,
        public ?string $memberId = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'eventId' => $this->eventId,
            'status' => $this->status,
            'instanceAt' => $this->instanceAt?->toIso8601String(),
            'memberId' => $this->memberId,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
