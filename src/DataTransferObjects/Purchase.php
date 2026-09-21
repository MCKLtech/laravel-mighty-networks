<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * A purchase of a plan by a member.
 *
 * Note on `memberEmail`: empty when the member has not consented to commercial
 *
 * email, and masked as `a***@***.***` unless the Network's plan includes
 * member-email visibility. The SDK never attempts to unmask it.
 *
 * `memberCategories` is typed as a string by the spec (it carries a serialised
 * list of category objects); it is preserved verbatim.
 */
final readonly class Purchase
{
    public function __construct(
        public int $memberId,
        public string $memberEmail,
        public PlanPrice $plan,
        public PurchaseDetail $purchase,
        public ?string $memberFirstName = null,
        public ?string $memberLastName = null,
        public ?string $memberTimeZone = null,
        public ?string $memberLocation = null,
        public ?int $memberReferralCount = null,
        public ?string $memberAvatar = null,
        public ?string $memberCategories = null,
        public ?string $memberPermalink = null,
        public ?string $memberAmbassadorLevel = null,
    ) {}

    /**
     * Create a Purchase from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: (int) ($data['member_id'] ?? 0),
            memberEmail: (string) ($data['member_email'] ?? ''),
            plan: PlanPrice::fromArray(self::objectOrEmpty($data['plan'] ?? null)),
            purchase: PurchaseDetail::fromArray(self::objectOrEmpty($data['purchase'] ?? null)),
            memberFirstName: self::stringOrNull($data['member_first_name'] ?? null),
            memberLastName: self::stringOrNull($data['member_last_name'] ?? null),
            memberTimeZone: self::stringOrNull($data['member_time_zone'] ?? null),
            memberLocation: self::stringOrNull($data['member_location'] ?? null),
            memberReferralCount: is_numeric($data['member_referral_count'] ?? null)
                ? (int) $data['member_referral_count']
                : null,
            memberAvatar: self::stringOrNull($data['member_avatar'] ?? null),
            memberCategories: self::stringOrNull($data['member_categories'] ?? null),
            memberPermalink: self::stringOrNull($data['member_permalink'] ?? null),
            memberAmbassadorLevel: self::stringOrNull($data['member_ambassador_level'] ?? null),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
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
}
