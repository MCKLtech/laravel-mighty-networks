<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for creating a comment.
 */
final readonly class NewCommentData
{
    public function __construct(
        public string $text,
        public ?int $replyToId = null,
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
                'text' => $this->text,
                'reply_to_id' => $this->replyToId,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
