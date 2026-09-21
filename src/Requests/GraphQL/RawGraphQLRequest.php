<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use Saloon\Http\Response;

/**
 * Escape hatch: run an arbitrary GraphQL document against a Network.
 *
 * Prefer the typed request classes. Use this when the operation is not modelled
 * yet, or when it selects a shape the typed DTOs do not cover.
 */
final class RawGraphQLRequest extends GraphQLRequest
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly string $document,
        private readonly array $variables = [],
    ) {
        parent::__construct($networkIdOrSubdomain);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        return $this->document;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function variables(): array
    {
        return $this->variables;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): mixed
    {
        return $this->dataFrom($response);
    }
}
