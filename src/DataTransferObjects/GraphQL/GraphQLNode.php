<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

/**
 * A Relay Node fetched through the top-level `node(id:)` / `nodes(ids:)` fields.
 *
 * The root `Node` interface only guarantees `id`, so a globally-identified node
 * carries its concrete `__typename` alongside the raw decoded payload. Select
 * the fields you need with a raw query when the concrete type matters.
 */
final readonly class GraphQLNode
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public ?string $typename = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $typename = $data['__typename'] ?? null;

        return new self(
            id: (string) ($data['id'] ?? ''),
            typename: is_string($typename) && $typename !== '' ? $typename : null,
            raw: $data,
        );
    }
}
