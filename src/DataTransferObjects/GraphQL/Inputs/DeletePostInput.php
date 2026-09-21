<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `DeletePostInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class DeletePostInput
{
    public function __construct(
        public string $id,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
