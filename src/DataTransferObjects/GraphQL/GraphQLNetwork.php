<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;

/**
 * A Network as returned by the Mighty API's GraphQL `Network` type.
 *
 * Distinct from {@see Member}
 * (Admin REST) and from the Admin REST Network response: GraphQL exposes `title`
 * (not `name`), a string `id` plus a `resourceId`, and camelCase timestamps.
 * Host-only fields (billing plan, feature toggles, branding images) are not
 * selected by the typed query; `$raw` retains the full decoded payload.
 */
final readonly class GraphQLNetwork
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $resourceId,
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?string $slug = null,
        public ?string $url = null,
        public ?string $avatarUrl = null,
        public ?string $headerUrl = null,
        public ?string $hostHeroImageUrl = null,
        public ?string $defaultLocale = null,
        public ?string $purpose = null,
        public bool $discoverable = false,
        public bool $explorable = false,
        public bool $joinable = false,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
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
            title: self::stringOrNull($data, 'title'),
            subtitle: self::stringOrNull($data, 'subtitle'),
            description: self::stringOrNull($data, 'description'),
            slug: self::stringOrNull($data, 'slug'),
            url: self::stringOrNull($data, 'url'),
            avatarUrl: self::stringOrNull($data, 'avatarUrl'),
            headerUrl: self::stringOrNull($data, 'headerUrl'),
            hostHeroImageUrl: self::stringOrNull($data, 'hostHeroImageUrl'),
            defaultLocale: self::stringOrNull($data, 'defaultLocale'),
            purpose: self::stringOrNull($data, 'purpose'),
            discoverable: (bool) ($data['discoverable'] ?? false),
            explorable: (bool) ($data['explorable'] ?? false),
            joinable: (bool) ($data['joinable'] ?? false),
            createdAt: self::dateOrNull($data['createdAt'] ?? null),
            updatedAt: self::dateOrNull($data['updatedAt'] ?? null),
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

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
