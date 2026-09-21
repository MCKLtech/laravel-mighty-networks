<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\AbuseReportContextType;
use MCKLtech\MightyNetworks\Enums\AbuseReportType;

/**
 * A report of content or a user that violates community guidelines.
 *
 * `reportType` and `contextType` map to `null` when the API returns a value
 * outside the documented set, so a new API category never breaks decoding.
 */
final readonly class AbuseReport
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public int $memberId,
        public int $targetableId,
        public string $targetableType,
        public int $networkId,
        public ?AbuseReportType $reportType = null,
        public ?string $reason = null,
        public ?int $contextId = null,
        public ?AbuseReportContextType $contextType = null,
        public ?int $spaceId = null,
        public ?CarbonImmutable $ignoredAt = null,
        public ?int $ignoredById = null,
    ) {}

    /**
     * Create an AbuseReport from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            memberId: (int) ($data['member_id'] ?? 0),
            targetableId: (int) ($data['targetable_id'] ?? 0),
            targetableType: (string) ($data['targetable_type'] ?? ''),
            networkId: (int) ($data['network_id'] ?? 0),
            reportType: self::reportType($data['report_type'] ?? null),
            reason: self::stringOrNull($data['reason'] ?? null),
            contextId: self::intOrNull($data['context_id'] ?? null),
            contextType: self::contextType($data['context_type'] ?? null),
            spaceId: self::intOrNull($data['space_id'] ?? null),
            ignoredAt: self::dateOrNull($data['ignored_at'] ?? null),
            ignoredById: self::intOrNull($data['ignored_by_id'] ?? null),
        );
    }

    private static function reportType(mixed $value): ?AbuseReportType
    {
        return is_string($value) && $value !== '' ? AbuseReportType::tryFrom($value) : null;
    }

    private static function contextType(mixed $value): ?AbuseReportContextType
    {
        return is_string($value) && $value !== '' ? AbuseReportContextType::tryFrom($value) : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
