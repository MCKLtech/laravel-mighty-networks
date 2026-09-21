<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldPrivacy;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;

/**
 * Request payload for creating a custom field.
 */
final readonly class NewCustomFieldData
{
    /**
     * @param  array<int, string>|null  $options
     */
    public function __construct(
        public string $title,
        public CustomFieldResponseType|string $responseType,
        public CustomFieldPrivacy|string $privacy,
        public string $responseBy = 'individual_member',
        public ?string $description = null,
        public ?string $placeholder = null,
        public ?bool $allowAdHoc = null,
        public ?int $maxResponses = null,
        public ?array $options = null,
        public ?int $minValue = null,
        public ?int $maxValue = null,
        public CustomFieldLocationGranularity|string|null $locationGranularity = null,
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
                'response_type' => $this->responseType instanceof CustomFieldResponseType
                    ? $this->responseType->value
                    : $this->responseType,
                'privacy' => $this->privacy instanceof CustomFieldPrivacy
                    ? $this->privacy->value
                    : $this->privacy,
                'response_by' => $this->responseBy,
                'allow_ad_hoc' => $this->allowAdHoc,
                'max_responses' => $this->maxResponses,
                'options' => $this->options,
                'min_value' => $this->minValue,
                'max_value' => $this->maxValue,
                'location_granularity' => $this->locationGranularity instanceof CustomFieldLocationGranularity
                    ? $this->locationGranularity->value
                    : $this->locationGranularity,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
