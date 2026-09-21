<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for creating a custom field option.
 */
final readonly class NewCustomFieldOptionData
{
    public function __construct(
        public string $title,
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
