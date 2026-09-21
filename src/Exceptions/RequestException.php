<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

use Saloon\Exceptions\Request\RequestException as SaloonRequestException;

/**
 * Base exception for any failed Mighty Networks request.
 *
 * Extending Saloon's exception tree keeps retries and `throw()` behaviour
 * working, because Saloon catches its own `RequestException` hierarchy.
 */
class RequestException extends SaloonRequestException
{
    //
}
