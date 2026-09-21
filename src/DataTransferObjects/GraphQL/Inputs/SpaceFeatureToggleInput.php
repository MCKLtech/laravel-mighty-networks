<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `SpaceFeatureToggleInput`, used by {@see UpdateSpaceInput}.
 */
final readonly class SpaceFeatureToggleInput
{
    public function __construct(
        public string $key,
        public bool $enabled,
    ) {}

    /**
     * @return array{key: string, enabled: bool}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'enabled' => $this->enabled];
    }
}
