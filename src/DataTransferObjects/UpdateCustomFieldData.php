<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\CustomFieldStatus;

/**
 * Request payload for updating a custom field (PATCH) or replacing it (PUT).
 */
final readonly class UpdateCustomFieldData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $placeholder = null,
        public CustomFieldStatus|string|null $status = null,
        public ?bool $notifyAllMembers = null,
        public ?bool $primary = null,
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
                'placeholder' => $this->placeholder,
                'status' => $this->status instanceof CustomFieldStatus ? $this->status->value : $this->status,
                'notify_all_members' => $this->notifyAllMembers,
                'primary' => $this->primary,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
