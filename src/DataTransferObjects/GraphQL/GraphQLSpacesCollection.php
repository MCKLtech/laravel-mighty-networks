<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Requests\GraphQL\Mutations\CreateSpaceMutation;

/**
 * A GraphQL `SpacesCollection` as returned by {@see CreateSpaceMutation}.
 */
final readonly class GraphQLSpacesCollection
{
    /**
     * @param  list<string>  $avatarUrls
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $url = null,
        public array $avatarUrls = [],
        public bool $visibleToMembers = false,
        public bool $explorable = false,
        public ?int $position = null,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $avatars = [];

        foreach (is_array($data['avatarUrls'] ?? null) ? $data['avatarUrls'] : [] as $url) {
            if (is_string($url)) {
                $avatars[] = $url;
            }
        }

        $description = $data['description'] ?? null;
        $url = $data['url'] ?? null;
        $position = $data['position'] ?? null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: is_string($description) && $description !== '' ? $description : null,
            url: is_string($url) && $url !== '' ? $url : null,
            avatarUrls: $avatars,
            visibleToMembers: (bool) ($data['visibleToMembers'] ?? false),
            explorable: (bool) ($data['explorable'] ?? false),
            position: is_numeric($position) ? (int) $position : null,
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
