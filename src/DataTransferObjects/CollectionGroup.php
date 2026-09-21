<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A Mighty Networks "Collection" — a named grouping of Spaces.
 *
 * Named `CollectionGroup` rather than `Collection` to avoid colliding with
 * `Illuminate\Support\Collection`.
 */
final readonly class CollectionGroup
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $name,
        public bool $visibleToMembers,
        public int $position,
        public bool $explorable,
        public ?string $description = null,
    ) {}

    /**
     * Create a CollectionGroup from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            name: (string) ($data['name'] ?? ''),
            visibleToMembers: (bool) ($data['visible_to_members'] ?? false),
            position: (int) ($data['position'] ?? 0),
            explorable: (bool) ($data['explorable'] ?? false),
            description: self::stringOrNull($data, 'description'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
