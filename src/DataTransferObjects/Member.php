<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\MemberType;

/**
 * A member of a Mighty Networks Network.
 *
 * Note on `email`: the Admin API returns an empty value when the member has not
 *
 * consented to commercial email, and masks it as `a***@***.***` unless the
 * Network's plan includes member-email visibility. This SDK never attempts to
 * unmask it.
 */
final readonly class Member
{
    /**
     * @param  array<int|string, mixed>  $categories
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $email,
        public MemberType $memberType,
        public string $permalink,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $timeZone = null,
        public ?string $location = null,
        public ?string $bio = null,
        public ?string $avatar = null,
        public ?int $referralCount = null,
        public array $categories = [],
        public ?string $ambassadorLevel = null,
        public ?CarbonImmutable $lastVisitedAt = null,
    ) {}

    /**
     * Create a Member from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            email: (string) ($data['email'] ?? ''),
            memberType: MemberType::tryFrom((string) ($data['member_type'] ?? '')) ?? MemberType::Full,
            permalink: (string) ($data['permalink'] ?? ''),
            firstName: self::stringOrNull($data, 'first_name'),
            lastName: self::stringOrNull($data, 'last_name'),
            timeZone: self::stringOrNull($data, 'time_zone'),
            location: self::stringOrNull($data, 'location'),
            bio: self::stringOrNull($data, 'bio'),
            avatar: self::stringOrNull($data, 'avatar'),
            referralCount: self::intOrNull($data, 'referral_count'),
            categories: self::arrayOrEmpty($data, 'categories'),
            ambassadorLevel: self::stringOrNull($data, 'ambassador_level'),
            lastVisitedAt: self::dateOrNull($data['last_visited_at'] ?? null),
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
     * @return array<int|string, mixed>
     */
    private static function arrayOrEmpty(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? self::date($value) : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
