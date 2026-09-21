<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;

/**
 * A configurable field added to a Network to capture extra member information.
 *
 * The Admin API returns a deliberately small representation: use the nested
 * options API to inspect the selectable values of dropdown fields.
 */
final readonly class CustomField
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $title,
        public CustomFieldResponseType $responseType,
        public ?CustomFieldLocationGranularity $locationGranularity = null,
    ) {}

    /**
     * Create a CustomField from a decoded Admin API payload.
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
            responseType: CustomFieldResponseType::tryFrom((string) ($data['response_type'] ?? ''))
                ?? CustomFieldResponseType::TextShort,
            locationGranularity: self::granularityOrNull($data['location_granularity'] ?? null),
        );
    }

    private static function granularityOrNull(mixed $value): ?CustomFieldLocationGranularity
    {
        return is_string($value) && $value !== ''
            ? CustomFieldLocationGranularity::tryFrom($value)
            : null;
    }

    private static function date(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : CarbonImmutable::now();
    }
}
