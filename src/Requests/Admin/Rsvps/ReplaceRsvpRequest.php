<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Rsvps;

use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateRsvpData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/events/{event_id}/rsvps/{id}/`
 *
 * Replaces the RSVP with the supplied representation.
 */
final class ReplaceRsvpRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
        protected readonly int $rsvpId,
        protected readonly UpdateRsvpData $data,
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

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Rsvp
    {
        $data = $response->json();

        return Rsvp::fromArray(is_array($data) ? $data : []);
    }
}
