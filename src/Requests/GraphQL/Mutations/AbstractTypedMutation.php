<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMutationPayload;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationDocument;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\Requests\GraphQL\GraphQLRequest;
use Saloon\Http\Response;

/**
 * Base class for the hand-written, typed mutation requests.
 *
 * Subclasses fix the {@see GraphQLMutation} operation, supply the payload
 * selection and the `input` variable, and inherit the shared response
 * hydration into a {@see GraphQLMutationPayload}. Callers may override the
 * default selection to fetch additional nested fields.
 */
abstract class AbstractTypedMutation extends GraphQLRequest
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain);
    }

    /**
     * The mutation this request executes.
     */
    abstract public function operation(): GraphQLMutation;

    /**
     * The payload selection used when the caller does not supply one.
     */
    abstract protected function defaultSelection(): Selection;

    /**
     * The effective payload selection.
     */
    public function selection(): Selection
    {
        return $this->selection ?? $this->defaultSelection();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        return MutationDocument::render($this->operation(), $this->selection());
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): GraphQLMutationPayload
    {
        $payload = $this->dataFrom($response)[$this->operation()->value] ?? null;

        return GraphQLMutationPayload::fromArray(is_array($payload) ? $payload : []);
    }
}
