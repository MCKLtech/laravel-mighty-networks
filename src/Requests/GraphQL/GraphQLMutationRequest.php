<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use InvalidArgumentException;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMutationPayload;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationDocument;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use Saloon\Http\Response;

/**
 * The generic escape hatch for any mutation on the Mighty API's GraphQL root.
 *
 * Builds `mutation OpName($input: OpNameInput!) { opName(input: $input) { ... } }`
 * from an operation (a {@see GraphQLMutation} case or a raw operation string),
 * a `variables` map (with the `input` value supplied by the caller) and a
 * {@see Selection} for the payload. Prefer the typed mutation requests; reach
 * for this when an operation has no typed class or you need a bespoke
 * selection.
 *
 * A raw operation string is resolved against the enum first. When it does not
 * match and no explicit `inputType` is supplied, the constructor throws
 * {@see InvalidArgumentException} rather than emit a document that will fail
 * server-side.
 */
final class GraphQLMutationRequest extends GraphQLRequest
{
    /**
     * The resolved enum case, when the operation is known.
     */
    private readonly ?GraphQLMutation $mutation;

    /**
     * The exact camelCase operation name sent in the document.
     */
    private readonly string $operation;

    private readonly string $inputType;

    private readonly Selection $selection;

    /**
     * @param  GraphQLMutation|string  $operation  An enum case or an exact/case-drifted operation name.
     * @param  array<string, mixed>  $variables  GraphQL variables; the `input` value belongs under the `input` key.
     * @param  Selection|null  $selection  Payload selection; defaults to `{ clientMutationId errors }`.
     * @param  string|null  $inputType  Required only when `$operation` is an unknown raw string.
     *
     * @throws InvalidArgumentException When a raw operation cannot be resolved and no input type is given.
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        GraphQLMutation|string $operation,
        private readonly array $variables = [],
        ?Selection $selection = null,
        ?string $inputType = null,
    ) {
        parent::__construct($networkIdOrSubdomain);

        $resolved = $operation instanceof GraphQLMutation
            ? $operation
            : GraphQLMutation::fromOperationName($operation);

        if ($resolved === null) {
            if ($inputType === null || trim($inputType) === '') {
                throw new InvalidArgumentException(sprintf(
                    'Unknown GraphQL mutation [%s]. Provide a GraphQLMutation case or an explicit input type.',
                    $operation,
                ));
            }

            $this->mutation = null;
            $this->operation = $operation;
            $this->inputType = $inputType;
        } else {
            $this->mutation = $resolved;
            $this->operation = $resolved->value;
            $this->inputType = $inputType ?? $resolved->inputType();
        }

        $this->selection = $selection ?? self::defaultSelection();
    }

    /**
     * The exact camelCase operation name sent in the document.
     */
    public function operation(): string
    {
        return $this->operation;
    }

    /**
     * The GraphQL input type declared in the document.
     */
    public function inputType(): string
    {
        return $this->inputType;
    }

    /**
     * The resolved enum case, or null for a raw operation outside the enum.
     */
    public function mutation(): ?GraphQLMutation
    {
        return $this->mutation;
    }

    /**
     * The payload selection set.
     */
    public function selection(): Selection
    {
        return $this->selection;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        return MutationDocument::renderRaw($this->operation, $this->inputType, $this->selection);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
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
    public function createDtoFromResponse(Response $response): GraphQLMutationPayload
    {
        $payload = $this->dataFrom($response)[$this->operation] ?? null;

        return GraphQLMutationPayload::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * The minimal payload selection: the mutation's own bookkeeping fields.
     */
    public static function defaultSelection(): Selection
    {
        return Selection::make()
            ->field('clientMutationId')
            ->field('errors');
    }
}
