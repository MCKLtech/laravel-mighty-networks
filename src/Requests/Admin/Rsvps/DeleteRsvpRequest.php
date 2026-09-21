<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Rsvps;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/events/{event_id}/rsvps/{id}/`
 */
final class DeleteRsvpRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
        protected readonly int $rsvpId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('events/%s/rsvps/%s/', $this->eventId, $this->rsvpId));
    }
}
