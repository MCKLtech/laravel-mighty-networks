<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * The authenticated user returned by the Admin REST `me` endpoint.
 *
 * Note on `email`: the Admin API returns an empty value when the user has not
 *
 * consented to commercial email, and masks it as `a***@***.***` unless the
 * Network's plan includes member-email visibility. This SDK never attempts to
 * unmask it.
 */
final readonly class NetworkUser
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $name,
        public string $email,
        public string $shortBio,
        public bool $admin,
        public ?CarbonImmutable $lastVisitedAt = null,
    ) {}

    /**
     * Create a NetworkUser from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            shortBio: (string) ($data['short_bio'] ?? ''),
            admin: (bool) ($data['admin'] ?? false),
            lastVisitedAt: self::dateOrNull($data['last_visited_at'] ?? null),
        );
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
