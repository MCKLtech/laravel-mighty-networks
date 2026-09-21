<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A tag that categorises and labels members of a Network.
 */
final readonly class Tag
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $title,
        public int $customFieldId,
        public ?string $description = null,
        public ?string $color = null,
    ) {}

    /**
     * Create a Tag from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
            title: (string) ($data['title'] ?? ''),
            customFieldId: (int) ($data['custom_field_id'] ?? 0),
            description: self::stringOrNull($data, 'description'),
            color: self::stringOrNull($data, 'color'),
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

    private static function date(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : CarbonImmutable::now();
    }
}
