<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Network;

use MCKLtech\MightyNetworks\DataTransferObjects\Me;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/me`
 *
 * Returns information about the authenticated access token.
 */
final class GetMeRequest extends AdminRequest
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
        return $this->networkEndpoint('me');
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Me
    {
        $data = $response->json();

        return Me::fromArray(is_array($data) ? $data : []);
    }
}
