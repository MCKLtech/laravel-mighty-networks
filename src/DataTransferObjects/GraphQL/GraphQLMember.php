<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberType;

/**
 * A member as returned by the Mighty API's GraphQL `Member` interface.
 *
 * This is deliberately **distinct** from {@see Member}
 * (Admin REST):
 *
 * - IDs are GraphQL `ID` strings (`id`) plus a `resourceId` for Admin API interop,
 *   rather than a plain integer `id`.
 * - Field names and value vocabularies are camelCase / GraphQL enums (for
 *   example `FULL_MEMBER`, `NETWORK_HOST`) rather than the REST snake_case shape.
 * - `memberType` and `networkRole` are kept as raw GraphQL strings because the
 *   GraphQL enum vocabularies do not match the Admin REST {@see MemberType}
 *   and {@see MembershipRole} values.
 *
 * Nested connection fields (`conversations`, `spaces`, `badges`, …) are not
 * selected by the typed queries and are intentionally not modelled here; fetch
 * them with a raw query when needed. `$raw` retains the full decoded payload.
 */
final readonly class GraphQLMember
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $resourceId,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $avatarUrl = null,
        public ?string $shortBio = null,
        public ?string $timeZone = null,
        public ?string $memberType = null,
        public ?string $networkRole = null,
        public bool $isLimitedMember = false,
        public ?int $referralCount = null,
        public ?int $followedMemberCount = null,
        public ?int $followerCount = null,
        public ?CarbonImmutable $joinedAt = null,
        public ?CarbonImmutable $lastActiveAt = null,
        public ?CarbonImmutable $updatedAt = null,
        public ?string $url = null,
        public ?string $ambassadorLevel = null,
        public ?int $ambassadorLevelId = null,
        public ?string $primaryProfileFieldLabel = null,
        public ?bool $privateChatEnabled = null,
        public ?bool $gamificationStreaksPublicCalendarEnabled = null,
        public ?bool $hasPushEnabled = null,
        public ?bool $hasConfirmedInstallation = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            resourceId: (string) ($data['resourceId'] ?? ''),
            name: self::stringOrNull($data, 'name'),
            email: self::stringOrNull($data, 'email'),
            firstName: self::stringOrNull($data, 'firstName'),
            lastName: self::stringOrNull($data, 'lastName'),
            avatarUrl: self::stringOrNull($data, 'avatarUrl'),
            shortBio: self::stringOrNull($data, 'shortBio'),
            timeZone: self::stringOrNull($data, 'timeZone'),
            memberType: self::stringOrNull($data, 'memberType'),
            networkRole: self::stringOrNull($data, 'networkRole'),
            isLimitedMember: (bool) ($data['isLimitedMember'] ?? false),
            referralCount: self::intOrNull($data, 'referralCount'),
            followedMemberCount: self::intOrNull($data, 'followedMemberCount'),
            followerCount: self::intOrNull($data, 'followerCount'),
            joinedAt: self::dateOrNull($data['joinedAt'] ?? null),
            lastActiveAt: self::dateOrNull($data['lastActiveAt'] ?? null),
            updatedAt: self::dateOrNull($data['updatedAt'] ?? null),
            url: self::stringOrNull($data, 'url'),
            ambassadorLevel: self::stringOrNull($data, 'ambassadorLevel'),
            ambassadorLevelId: self::intOrNull($data, 'ambassadorLevelId'),
            primaryProfileFieldLabel: self::stringOrNull($data, 'primaryProfileFieldLabel'),
            privateChatEnabled: self::boolOrNull($data, 'privateChatEnabled'),
            gamificationStreaksPublicCalendarEnabled: self::boolOrNull($data, 'gamificationStreaksPublicCalendarEnabled'),
            hasPushEnabled: self::boolOrNull($data, 'hasPushEnabled'),
            hasConfirmedInstallation: self::boolOrNull($data, 'hasConfirmedInstallation'),
            raw: $data,
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
    private static function boolOrNull(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
