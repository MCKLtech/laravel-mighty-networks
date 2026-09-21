<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for updating a collection. Null fields are left unchanged.
 */
final readonly class UpdateCollectionGroupData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?bool $visibleToMembers = null,
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
                'description' => $this->description,
                'visible_to_members' => $this->visibleToMembers,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
