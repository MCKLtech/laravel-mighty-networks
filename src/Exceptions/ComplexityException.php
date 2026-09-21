<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when a GraphQL query exceeds the API's complexity cap (1500).
 *
 * The API rejects these pre-execution with a top-level error that has no
 * `path` and no `extensions.code`, plus a `data` of `null`.
 */
final class ComplexityException extends RequestException
{
    //
}
