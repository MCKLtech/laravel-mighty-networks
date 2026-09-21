<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `BanMemberInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class BanMemberInput
{
    public function __construct(
        public string $memberId,
        public ?string $reason = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'memberId' => $this->memberId,
            'reason' => $this->reason,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
