<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `CreateReactionInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreateReactionInput
{
    public function __construct(
        public string $emoji,
        public string $targetId,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'emoji' => $this->emoji,
            'targetId' => $this->targetId,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
