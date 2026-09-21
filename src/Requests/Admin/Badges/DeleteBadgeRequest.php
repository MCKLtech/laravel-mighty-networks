<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Badges;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/badges/{id}/`
 */
final class DeleteBadgeRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

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
}
