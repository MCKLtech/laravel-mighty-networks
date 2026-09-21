<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Badges;

use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/badges/{id}/`
 */
final class GetBadgeRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $id,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('badges/%d/', $this->id));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Badge
    {
        $data = $response->json();

        return Badge::fromArray(is_array($data) ? $data : []);
    }
}
