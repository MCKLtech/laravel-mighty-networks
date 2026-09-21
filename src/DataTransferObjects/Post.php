<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\PostStatus;
use MCKLtech\MightyNetworks\Enums\PostType;

/**
 * A post or article within a Network space.
 */
final readonly class Post
{
    /**
     * @param  array<int, string>  $images
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public int $creatorId,
        public int $spaceId,
        public string $summary,
        public string $description,
        public PostType $postType,
        public array $images,
        public string $title,
        public PostStatus $status,
        public CarbonImmutable $publishedAt,
        public CarbonImmutable $lastActivityAt,
        public string $contentType,
        public bool $commentsEnabled,
        public string $permalink,
    ) {}

    /**
     * Create a Post from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            creatorId: (int) ($data['creator_id'] ?? 0),
            spaceId: (int) ($data['space_id'] ?? 0),
            summary: (string) ($data['summary'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            postType: self::postType($data['post_type'] ?? null),
            images: self::stringList($data, 'images'),
            title: (string) ($data['title'] ?? ''),
            status: self::postStatus($data['status'] ?? null),
            publishedAt: self::date((string) ($data['published_at'] ?? '')),
            lastActivityAt: self::date((string) ($data['last_activity_at'] ?? '')),
            contentType: (string) ($data['content_type'] ?? ''),
            commentsEnabled: (bool) ($data['comments_enabled'] ?? false),
            permalink: (string) ($data['permalink'] ?? ''),
        );
    }

    private static function postType(mixed $value): PostType
    {
        return is_string($value)
            ? (PostType::tryFrom($value) ?? PostType::Post)
            : PostType::Post;
    }

    private static function postStatus(mixed $value): PostStatus
    {
        return is_string($value)
            ? (PostStatus::tryFrom($value) ?? PostStatus::Posted)
            : PostStatus::Posted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private static function stringList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item)));
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
