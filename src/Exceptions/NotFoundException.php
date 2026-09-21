<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when the requested resource does not exist (HTTP 404, or the GraphQL
 * `NOT_FOUND` error code).
 */
final class NotFoundException extends RequestException
{
    //
}
