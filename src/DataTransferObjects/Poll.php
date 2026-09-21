<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * A poll, hot/cold, percentage or open-ended question post in a Network.
 */
final readonly class Poll
{
    /**
     * @param  array<string, mixed>  $creator
     * @param  array<string, mixed>  $space
     * @param  array<int, string>  $images
     * @param  array<int, array<string, mixed>>  $choices
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $postType,
        public string $title,
        public string $status,
        public string $permalink,
        public bool $commentsEnabled,
        public array $creator = [],
        public array $space = [],
        public array $images = [],
        public array $choices = [],
        public ?string $pollType = null,
        public ?string $description = null,
        public ?CarbonImmutable $publishedAt = null,
        public ?CarbonImmutable $lastActivityAt = null,
    ) {}

    /**
     * Create a Poll from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date($data['created_at'] ?? null),
            updatedAt: self::date($data['updated_at'] ?? null),
            postType: (string) ($data['post_type'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            permalink: (string) ($data['permalink'] ?? ''),
            commentsEnabled: (bool) ($data['comments_enabled'] ?? false),
            creator: self::object($data, 'creator'),
            space: self::object($data, 'space'),
            images: self::stringList($data, 'images'),
            choices: self::objectList($data, 'choices'),
            pollType: self::stringOrNull($data, 'poll_type'),
            description: self::stringOrNull($data, 'description'),
            publishedAt: self::dateOrNull($data['published_at'] ?? null),
            lastActivityAt: self::dateOrNull($data['last_activity_at'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function object(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
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

        return array_values(array_filter($value, is_string(...)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private static function objectList(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
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

    private static function date(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)
            : CarbonImmutable::now();
    }
}
