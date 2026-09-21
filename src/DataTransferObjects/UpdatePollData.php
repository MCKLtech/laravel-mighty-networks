<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for updating a poll or question.
 */
final readonly class UpdatePollData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
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
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
