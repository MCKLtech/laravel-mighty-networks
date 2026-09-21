<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `CreateSpaceMembershipsInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreateSpaceMembershipsInput
{
    /**
     * @param  list<string>  $spaceIds
     */
    public function __construct(
        public string $memberId,
        public array $spaceIds,
        public ?string $platform = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'memberId' => $this->memberId,
            'spaceIds' => array_values($this->spaceIds),
            'platform' => $this->platform,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
