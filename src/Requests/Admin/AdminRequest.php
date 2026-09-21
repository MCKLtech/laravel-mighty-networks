<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin;

use Saloon\Http\Request;

/**
 * Base class for Admin REST requests. Every Admin path is scoped to a Network.
 */
abstract class AdminRequest extends Request
{
    public function __construct(
        protected readonly int|string $networkId,
    ) {}

    /**
     * Build an endpoint beneath `networks/{network_id}/`.
     */
    protected function networkEndpoint(string $path): string
    {
        return sprintf('networks/%s/%s', $this->networkId, ltrim($path, '/'));
    }
}
