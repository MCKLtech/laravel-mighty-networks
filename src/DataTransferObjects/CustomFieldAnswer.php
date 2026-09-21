<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A member's answer to a custom field.
 *
 * A single row answer (one row per member) carries `id`, `user_id` and the
 * scalar value fields. Fields whose answers span several rows
 * (`multi_location`) instead return the member's whole answer, which carries
 * `member_id` and a `locations` list but no row `id`. Both shapes are
 * represented by this DTO.
 */
final readonly class CustomFieldAnswer
{
    /**
     * @param  array<int, array<string, mixed>>  $segments
     * @param  array<int, array<string, mixed>>  $locations
     */
    public function __construct(
        public int $customFieldId,
        public ?int $id = null,
        public ?int $userId = null,
        public ?int $memberId = null,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
        public ?CarbonImmutable $lastEditedAt = null,
        public ?string $text = null,
        public ?int $number = null,
        public ?bool $booleanValue = null,
        public ?string $date = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?int $month = null,
        public ?int $day = null,
        public ?string $url = null,
        public ?string $phoneNumber = null,
        public array $segments = [],
        public array $locations = [],
    ) {}

    /**
     * Create a CustomFieldAnswer from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customFieldId: (int) ($data['custom_field_id'] ?? 0),
            id: self::intOrNull($data, 'id'),
            userId: self::intOrNull($data, 'user_id'),
            memberId: self::intOrNull($data, 'member_id'),
            createdAt: self::dateOrNull($data['created_at'] ?? null),
            updatedAt: self::dateOrNull($data['updated_at'] ?? null),
            lastEditedAt: self::dateOrNull($data['last_edited_at'] ?? null),
            text: self::stringOrNull($data, 'text'),
            number: self::intOrNull($data, 'number'),
            booleanValue: self::boolOrNull($data, 'boolean_value'),
            date: self::stringOrNull($data, 'date'),
            latitude: self::floatOrNull($data, 'latitude'),
            longitude: self::floatOrNull($data, 'longitude'),
            startDate: self::stringOrNull($data, 'start_date'),
            endDate: self::stringOrNull($data, 'end_date'),
            month: self::intOrNull($data, 'month'),
            day: self::intOrNull($data, 'day'),
            url: self::stringOrNull($data, 'url'),
            phoneNumber: self::stringOrNull($data, 'phone_number'),
            segments: self::objectList($data, 'segments'),
            locations: self::objectList($data, 'locations'),
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

    /**
     * @param  array<string, mixed>  $data
     */
    private static function intOrNull(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function floatOrNull(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function boolOrNull(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private static function objectList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
