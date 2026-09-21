<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\PollType;

/**
 * Request payload for creating a poll or question.
 *
 * `choices` is required by the API for `multiple_choice`, `hot_cold` and
 * `percentage` polls, and ignored for `question`.
 */
final readonly class NewPollData
{
    /**
     * @param  array<int, string>|null  $choices
     */
    public function __construct(
        public int $spaceId,
        public string $title,
        public PollType|string $pollType,
        public ?string $description = null,
        public ?array $choices = null,
        public ?bool $notify = null,
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
                'poll_type' => $this->pollType instanceof PollType ? $this->pollType->value : $this->pollType,
                'description' => $this->description,
                'choices' => $this->choices,
                'notify' => $this->notify,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
