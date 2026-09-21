<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when the API rejects the submitted payload (HTTP 422, or the GraphQL
 * `BAD_USER_INPUT` error code).
 */
final class ValidationException extends RequestException
{
    //
}
