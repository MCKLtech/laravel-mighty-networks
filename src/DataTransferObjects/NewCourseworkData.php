<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\CompletionCriteria;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Enums\UnlockingCriteria;

/**
 * Request payload for creating a coursework item (lesson, quiz, or section).
 */
final readonly class NewCourseworkData
{
    public function __construct(
        public CourseworkType|string $type,
        public ?int $parentId = null,
        public ?string $title = null,
        public ?string $description = null,
        public CourseworkStatus|string|null $status = null,
        public CompletionCriteria|string|null $completionCriteria = null,
        public UnlockingCriteria|string|null $unlockingCriteria = null,
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
                'type' => $this->type instanceof CourseworkType ? $this->type->value : $this->type,
                'parent_id' => $this->parentId,
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status instanceof CourseworkStatus ? $this->status->value : $this->status,
                'completion_criteria' => $this->completionCriteria instanceof CompletionCriteria ? $this->completionCriteria->value : $this->completionCriteria,
                'unlocking_criteria' => $this->unlockingCriteria instanceof UnlockingCriteria ? $this->unlockingCriteria->value : $this->unlockingCriteria,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
