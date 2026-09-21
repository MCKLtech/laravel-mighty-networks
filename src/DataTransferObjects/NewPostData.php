<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\PostType;

/**
 * Request payload for creating a post.
 */
final readonly class NewPostData
{
    public function __construct(
        public int $spaceId,
        public string $title,
        public ?string $description = null,
        public PostType|string|null $postType = null,
    ) {}

    /**
     * Convert to the snake_case API payload, omitting null values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'space_id' => $this->spaceId,
                'title' => $this->title,
                'description' => $this->description,
                'post_type' => $this->postType instanceof PostType ? $this->postType->value : $this->postType,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
