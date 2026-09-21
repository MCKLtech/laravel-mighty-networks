<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `UpdateSpaceInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class UpdateSpaceInput
{
    /**
     * @param  list<SpaceFeatureToggleInput>|null  $featureToggles
     */
    public function __construct(
        public string $spaceId,
        public ?string $title = null,
        public ?string $description = null,
        public ?array $featureToggles = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $toggles = null;

        if ($this->featureToggles !== null) {
            $toggles = array_map(
                static fn (SpaceFeatureToggleInput $toggle): array => $toggle->toArray(),
                $this->featureToggles,
            );
        }

        return array_filter([
            'spaceId' => $this->spaceId,
            'title' => $this->title,
            'description' => $this->description,
            'featureToggles' => $toggles,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
