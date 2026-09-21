<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * A snapshot of the plan attached to a subscription or purchase.
 *
 * Monetary value: `amount` is expressed in the smallest unit of `currency`
 * (e.g. **cents** for USD). The SDK never converts or divides it.
 *
 * `id` and `name` are nullable because the underlying bundle may have been
 * deleted after the subscription/purchase was recorded.
 */
final readonly class PlanPrice
{
    public function __construct(
        public int $amount = 0,
        public string $currency = '',
        public string $interval = '',
        public string $type = '',
        public bool $hasFreeTrial = false,
        public ?int $id = null,
        public ?string $name = null,
    ) {}

    /**
     * Create a PlanPrice from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: is_numeric($data['amount'] ?? null) ? (int) $data['amount'] : 0,
            currency: (string) ($data['currency'] ?? ''),
            interval: (string) ($data['interval'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            hasFreeTrial: (bool) ($data['has_free_trial'] ?? false),
            id: is_numeric($data['id'] ?? null) ? (int) $data['id'] : null,
            name: is_string($data['name'] ?? null) && $data['name'] !== '' ? $data['name'] : null,
        );
    }
}
