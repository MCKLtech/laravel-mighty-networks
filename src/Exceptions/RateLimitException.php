<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

/**
 * Thrown when a request exceeds a monthly quota or a server-side throttle
 * (HTTP 429, or the GraphQL `THROTTLED` error code).
 */
final class RateLimitException extends RequestException
{
    //
}
