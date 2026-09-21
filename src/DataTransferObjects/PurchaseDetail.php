<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * Detailed information for a one-time purchase.
 */
final readonly class PurchaseDetail
{
    public function __construct(
        public int $id,
        public CarbonImmutable $purchasedAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?int $taxPercent = null,
        public ?string $paymentPlatform = null,
    ) {}

    /**
     * Create a PurchaseDetail from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            purchasedAt: self::dateOrNow($data['purchased_at'] ?? null),
            createdAt: self::dateOrNow($data['created_at'] ?? null),
            updatedAt: self::dateOrNow($data['updated_at'] ?? null),
            taxPercent: is_numeric($data['tax_percent'] ?? null) ? (int) $data['tax_percent'] : null,
            paymentPlatform: is_string($data['payment_platform'] ?? null) && $data['payment_platform'] !== ''
                ? $data['payment_platform']
                : null,
        );
    }

    private static function dateOrNow(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : CarbonImmutable::now();
    }
}
