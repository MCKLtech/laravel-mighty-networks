<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

use Carbon\CarbonImmutable;

/**
 * A GraphQL `Rsvp` as returned by the RSVP mutations.
 */
final readonly class GraphQLRsvp
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $resourceId,
        public string $status,
        public ?CarbonImmutable $instanceAt = null,
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
            status: (string) ($data['status'] ?? ''),
            instanceAt: self::dateOrNull($data['instanceAt'] ?? null),
            createdAt: self::dateOrNull($data['createdAt'] ?? null),
            updatedAt: self::dateOrNull($data['updatedAt'] ?? null),
            raw: $data,
        );
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
