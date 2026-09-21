<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A reaction (emoji) applied to a post or comment.
 */
final readonly class Reaction
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public int $spaceId,
        public int $networkId,
        public int $targetableId,
        public string $targetableType,
        public int $targetableSpaceId,
        public string $emoji,
        public string $baseEmoji,
        public int $memberId,
    ) {}

    /**
     * Create a Reaction from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            spaceId: (int) ($data['space_id'] ?? 0),
            networkId: (int) ($data['network_id'] ?? 0),
            targetableId: (int) ($data['targetable_id'] ?? 0),
            targetableType: (string) ($data['targetable_type'] ?? ''),
            targetableSpaceId: (int) ($data['targetable_space_id'] ?? 0),
            emoji: (string) ($data['emoji'] ?? ''),
            baseEmoji: (string) ($data['base_emoji'] ?? ''),
            memberId: (int) ($data['member_id'] ?? 0),
        );
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
