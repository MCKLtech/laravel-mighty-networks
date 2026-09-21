<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

/**
 * A single feature flag on a GraphQL {@see GraphQLBillingPlan}.
 */
final readonly class GraphQLBillingFeature
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $state = null,
        public int|string|null $value = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $key = $data['key'] ?? '';
        $label = $data['label'] ?? '';
        $state = $data['state'] ?? null;
        $value = $data['value'] ?? null;

        return new self(
            key: is_string($key) ? $key : (string) $key,
            label: is_string($label) ? $label : (string) $label,
            state: is_string($state) && $state !== '' ? $state : null,
            value: is_int($value) || is_string($value) ? $value : null,
            raw: $data,
        );
    }
}
