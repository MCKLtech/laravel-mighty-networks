<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\PlanStatus;
use MCKLtech\MightyNetworks\Enums\PricingType;

/**
 * A purchasable offering in a Network.
 *
 * Note on `status` and `pricingType`: the API documents a fixed set of values,
 * but new values may appear without a spec update, so unrecognised values map to
 * `null` rather than throwing.
 */
final readonly class Plan
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $name,
        public string $permalink,
        public ?string $description = null,
        public ?PlanStatus $status = null,
        public ?PricingType $pricingType = null,
        public ?bool $visibleToMembers = null,
        public ?bool $external = null,
        public ?bool $multiple = null,
    ) {}

    /**
     * Create a Plan from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
            name: (string) ($data['name'] ?? ''),
            permalink: (string) ($data['permalink'] ?? ''),
            description: self::stringOrNull($data['description'] ?? null),
            status: self::status($data['status'] ?? null),
            pricingType: self::pricingType($data['pricing_type'] ?? null),
            visibleToMembers: self::boolOrNull($data['visible_to_members'] ?? null),
            external: self::boolOrNull($data['external'] ?? null),
            multiple: self::boolOrNull($data['multiple'] ?? null),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }

    private static function status(mixed $value): ?PlanStatus
    {
        return is_string($value) && $value !== '' ? PlanStatus::tryFrom($value) : null;
    }

    private static function pricingType(mixed $value): ?PricingType
    {
        return is_string($value) && $value !== '' ? PricingType::tryFrom($value) : null;
    }

    private static function date(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : CarbonImmutable::now();
    }
}
