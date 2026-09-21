<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\DataTransferObjects\Me;
use MCKLtech\MightyNetworks\DataTransferObjects\Network;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Requests\Admin\Network\GetMeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Network\GetNetworkRequest;
use Saloon\Http\Response;

/**
 * The public Network API: the current Network's details and the authorization
 * context of the requesting token.
 */
final class NetworkResource extends Resource
{
    /**
     * Fetch the details of the Network that owns the requesting API key.
     */
    public function details(): Network
    {
        return $this->networkFrom(
            $this->connector()->send(new GetNetworkRequest($this->networkId())),
        );
    }

    /**
     * Fetch information about the authenticated access token.
     */
    public function me(): Me
    {
        return $this->meFrom(
            $this->connector()->send(new GetMeRequest($this->networkId())),
        );
    }

    private function networkFrom(Response $response): Network
    {
        $dto = $response->dto();

        if (! $dto instanceof Network) {
            throw new MightyNetworksException('Expected a Network from the network endpoint.');
        }

        return $dto;
    }

    private function meFrom(Response $response): Me
    {
        $dto = $response->dto();

        if (! $dto instanceof Me) {
            throw new MightyNetworksException('Expected a Me from the me endpoint.');
        }

        return $dto;
    }
}
