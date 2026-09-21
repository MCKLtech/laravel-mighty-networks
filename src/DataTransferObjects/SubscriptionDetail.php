<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * Detailed billing information for a payment subscription.
 */
final readonly class SubscriptionDetail
{
    public function __construct(
        public int $id,
        public CarbonImmutable $purchasedAt,
        public ?CarbonImmutable $currentPeriodStart = null,
        public ?CarbonImmutable $currentPeriodEnd = null,
        public ?string $paymentPlatform = null,
        public ?CarbonImmutable $canceledAt = null,
        public ?int $trialLength = null,
        public ?CarbonImmutable $trialStart = null,
        public ?CarbonImmutable $trialEnd = null,
    ) {}

    /**
     * Create a SubscriptionDetail from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            purchasedAt: self::dateOrNow($data['purchased_at'] ?? null),
            currentPeriodStart: self::dateOrNull($data['current_period_start'] ?? null),
            currentPeriodEnd: self::dateOrNull($data['current_period_end'] ?? null),
            paymentPlatform: self::stringOrNull($data['payment_platform'] ?? null),
            canceledAt: self::dateOrNull($data['canceled_at'] ?? null),
            trialLength: is_numeric($data['trial_length'] ?? null) ? (int) $data['trial_length'] : null,
            trialStart: self::dateOrNull($data['trial_start'] ?? null),
            trialEnd: self::dateOrNull($data['trial_end'] ?? null),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private static function dateOrNow(mixed $value): CarbonImmutable
    {
        return self::dateOrNull($value) ?? CarbonImmutable::now();
    }
}
