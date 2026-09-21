<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `CreateSpaceInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreateSpaceInput
{
    /**
     * @param  list<string>|null  $enabledFeatureKeys
     */
    public function __construct(
        public string $templateCanonicalName,
        public string $title,
        public ?string $collectionName = null,
        public ?string $description = null,
        public ?array $enabledFeatureKeys = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'templateCanonicalName' => $this->templateCanonicalName,
            'title' => $this->title,
            'collectionName' => $this->collectionName,
            'description' => $this->description,
            'enabledFeatureKeys' => $this->enabledFeatureKeys,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
