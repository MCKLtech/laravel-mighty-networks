<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNetwork;

/**
 * A Network as returned by the Admin REST API.
 *
 * Distinct from {@see GraphQLNetwork},
 * which models the GraphQL `Network` type.
 */
final readonly class Network
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $subdomain,
        public string $title,
        public string $subtitle,
        public string $purpose,
        public string $description,
    ) {}

    /**
     * Create a Network from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            subdomain: (string) ($data['subdomain'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            subtitle: (string) ($data['subtitle'] ?? ''),
            purpose: (string) ($data['purpose'] ?? ''),
            description: (string) ($data['description'] ?? ''),
        );
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
