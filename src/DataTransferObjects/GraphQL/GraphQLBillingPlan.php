<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

/**
 * A Mighty Networks billing plan tier, returned by the `billingPlan` query root.
 *
 * Mirrors the GraphQL `BillingPlan` type's leaf fields; `features` is expanded
 * into {@see GraphQLBillingFeature} value objects.
 */
final readonly class GraphQLBillingPlan
{
    /**
     * @param  list<GraphQLBillingFeature>  $features
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $canonicalName,
        public string $displayName,
        public array $features = [],
        public ?string $group = null,
        public ?int $level = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $canonicalName = $data['canonicalName'] ?? '';
        $displayName = $data['displayName'] ?? '';
        $group = $data['group'] ?? null;
        $level = $data['level'] ?? null;

        $features = [];

        foreach (is_array($data['features'] ?? null) ? $data['features'] : [] as $feature) {
            if (is_array($feature)) {
                $features[] = GraphQLBillingFeature::fromArray($feature);
            }
        }

        return new self(
            canonicalName: is_string($canonicalName) ? $canonicalName : (string) $canonicalName,
            displayName: is_string($displayName) ? $displayName : (string) $displayName,
            features: $features,
            group: is_string($group) && $group !== '' ? $group : null,
            level: is_numeric($level) ? (int) $level : null,
            raw: $data,
        );
    }
}
