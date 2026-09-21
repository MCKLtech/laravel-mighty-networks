<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Network;

use MCKLtech\MightyNetworks\DataTransferObjects\Network;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/`
 *
 * Returns the Network that owns the requesting API key. Note the trailing
 * slash: the spec documents this path with one.
 */
final class GetNetworkRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(int|string $networkId)
    {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('');
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Network
    {
        $data = $response->json();

        return Network::fromArray(is_array($data) ? $data : []);
    }
}
