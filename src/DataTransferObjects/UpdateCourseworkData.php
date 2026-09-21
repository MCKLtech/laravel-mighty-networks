<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\CompletionCriteria;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\UnlockingCriteria;

/**
 * Request payload for updating a coursework item. Null fields are left unchanged.
 */
final readonly class UpdateCourseworkData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public CourseworkStatus|string|null $status = null,
        public ?int $parentId = null,
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
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status instanceof CourseworkStatus ? $this->status->value : $this->status,
                'parent_id' => $this->parentId,
                'completion_criteria' => $this->completionCriteria instanceof CompletionCriteria ? $this->completionCriteria->value : $this->completionCriteria,
                'unlocking_criteria' => $this->unlockingCriteria instanceof UnlockingCriteria ? $this->unlockingCriteria->value : $this->unlockingCriteria,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
