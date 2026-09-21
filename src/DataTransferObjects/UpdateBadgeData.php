<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for updating a badge.
 */
final readonly class UpdateBadgeData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $color = null,
        public ?int $avatarId = null,
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
                'color' => $this->color,
                'avatar_id' => $this->avatarId,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
