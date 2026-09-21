<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when a request is rejected as unauthenticated (HTTP 401, or the
 * GraphQL `UNAUTHENTICATED` error code).
 */
final class AuthenticationException extends RequestException
{
    //
}
