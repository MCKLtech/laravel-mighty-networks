<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\Requests\GraphQL\GraphQLMutationRequest;

/**
 * Renders a GraphQL mutation document from its operation, input type and
 * payload selection.
 *
 * Every mutation in the Mighty API's SDL takes a single `input` argument, so
 * the document is always
 * `mutation OpName($input: OpNameInput!) { opName(input: $input) { ... } }`.
 * Callers supply the payload selection; this helper only assembles the
 * envelope. It is shared by {@see GraphQLMutationRequest}
 * and the typed mutation requests so the envelope is defined exactly once.
 */
final class MutationDocument
{
    /**
     * @param  Selection  $selection  The payload selection set (a bare, unnamed selection).
     */
    public static function render(GraphQLMutation $operation, Selection $selection): string
    {
        return self::renderRaw($operation->value, $operation->inputType(), $selection);
    }

    /**
     * Render a mutation document from raw operation and input type strings.
     *
     * Used by the generic request for operations outside the enum; callers are
     * responsible for the input type matching the operation.
     */
    public static function renderRaw(string $operation, string $inputType, Selection $selection): string
    {
        $operationName = ucfirst($operation);

        return sprintf(
            'mutation %s($input: %s!) { %s(input: $input) %s }',
            $operationName,
            $inputType,
            $operation,
            $selection->render(),
        );
    }
}
