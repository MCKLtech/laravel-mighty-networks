<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * A space's position within a collection, as returned by the reorder endpoint.
 */
final readonly class CollectionOrderItem
{
    public function __construct(
        public int $id,
        public string $name,
        public int $position,
    ) {}

    /**
     * Create a CollectionOrderItem from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            position: (int) ($data['position'] ?? 0),
        );
    }
}
