<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when a request is authenticated but not permitted (HTTP 403, or the
 * GraphQL `FORBIDDEN` error code).
 */
final class ForbiddenException extends RequestException
{
    //
}
