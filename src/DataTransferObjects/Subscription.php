<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A payment subscription for a plan.
 *
 * Note on `email`: empty when the member has not consented to commercial email,
 *
 * and masked as `a***@***.***` unless the Network's plan includes member-email
 * visibility. The SDK never attempts to unmask it.
 */
final readonly class Subscription
{
    /**
     * @param  array<int|string, mixed>  $categories
     */
    public function __construct(
        public int $memberId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $email,
        public string $permalink,
        public PlanPrice $plan,
        public SubscriptionDetail $subscription,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $timeZone = null,
        public ?string $location = null,
        public ?int $referralCount = null,
        public ?string $avatar = null,
        public array $categories = [],
        public ?string $ambassadorLevel = null,
    ) {}

    /**
     * Create a Subscription from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: (int) ($data['member_id'] ?? 0),
            createdAt: self::dateOrNow($data['created_at'] ?? null),
            updatedAt: self::dateOrNow($data['updated_at'] ?? null),
            email: (string) ($data['email'] ?? ''),
            permalink: (string) ($data['permalink'] ?? ''),
            plan: PlanPrice::fromArray(self::objectOrEmpty($data['plan'] ?? null)),
            subscription: SubscriptionDetail::fromArray(self::objectOrEmpty($data['subscription'] ?? null)),
            firstName: self::stringOrNull($data['first_name'] ?? null),
            lastName: self::stringOrNull($data['last_name'] ?? null),
            timeZone: self::stringOrNull($data['time_zone'] ?? null),
            location: self::stringOrNull($data['location'] ?? null),
            referralCount: is_numeric($data['referral_count'] ?? null) ? (int) $data['referral_count'] : null,
            avatar: self::stringOrNull($data['avatar'] ?? null),
            categories: self::arrayOrEmpty($data['categories'] ?? null),
            ambassadorLevel: self::stringOrNull($data['ambassador_level'] ?? null),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Normalise a decoded nested object into a string-keyed array.
     *
     * @return array<string, mixed>
     */
    private static function objectOrEmpty(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalised = [];

        foreach ($value as $key => $item) {
            $normalised[(string) $key] = $item;
        }

        return $normalised;
    }

    private static function dateOrNow(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : CarbonImmutable::now();
    }
}
