<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A comment on a post (or other commentable content).
 */
final readonly class Comment
{
    /**
     * @param  array<int|string, mixed>  $files
     * @param  array<int|string, mixed>|null  $embeddedLink
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public int $targetableId,
        public string $targetableType,
        public string $text,
        public bool $replyable,
        public int $depth,
        public int $cheerCount,
        public int $replyCount,
        public int $authorId,
        public int $spaceId,
        public string $permalink,
        public ?int $replyToId = null,
        public array $files = [],
        public ?array $embeddedLink = null,
    ) {}

    /**
     * Create a Comment from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            targetableId: (int) ($data['targetable_id'] ?? 0),
            targetableType: (string) ($data['targetable_type'] ?? ''),
            text: (string) ($data['text'] ?? ''),
            replyable: (bool) ($data['replyable'] ?? false),
            depth: (int) ($data['depth'] ?? 0),
            cheerCount: (int) ($data['cheer_count'] ?? 0),
            replyCount: (int) ($data['reply_count'] ?? 0),
            authorId: (int) ($data['author_id'] ?? 0),
            spaceId: (int) ($data['space_id'] ?? 0),
            permalink: (string) ($data['permalink'] ?? ''),
            replyToId: self::intOrNull($data, 'reply_to_id'),
            files: self::arrayOrEmpty($data, 'files'),
            embeddedLink: self::objectOrNull($data, 'embedded_link'),
        );
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>|null
     */
    private static function objectOrNull(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
