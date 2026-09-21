<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Connectors\AdminConnector;

/**
 * Base class for API resources. Holds the connector and the Network scope that
 * each resource needs to build its requests.
 */
abstract class Resource
{
    public function __construct(
        protected readonly AdminConnector $connector,
        protected readonly int|string $networkId,
    ) {}

    /**
     * The Admin REST connector backing this resource.
     */
    protected function connector(): AdminConnector
    {
        return $this->connector;
    }

    /**
     * The Network this resource is scoped to.
     */
    protected function networkId(): int|string
    {
        return $this->networkId;
    }
}
