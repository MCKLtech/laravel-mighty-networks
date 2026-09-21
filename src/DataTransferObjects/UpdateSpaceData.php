<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for updating a space. Null fields are left unchanged.
 */
final readonly class UpdateSpaceData
{
    public function __construct(
        public ?string $name = null,
        public ?int $collectionId = null,
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
                'name' => $this->name,
                'collection_id' => $this->collectionId,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
