<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for creating a space.
 */
final readonly class NewSpaceData
{
    public function __construct(
        public string $name,
    ) {}

    /**
     * Convert to the snake_case API payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
