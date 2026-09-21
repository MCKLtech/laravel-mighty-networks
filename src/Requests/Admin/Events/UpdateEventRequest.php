<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Events;

use MCKLtech\MightyNetworks\DataTransferObjects\Event;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateEventData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PATCH networks/{network_id}/events/{id}/`
 *
 * A partial update: only the fields present on the DTO are sent.
 */
final class UpdateEventRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
        protected readonly UpdateEventData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('events/%s/', $this->eventId));
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
    public function createDtoFromResponse(Response $response): Event
    {
        $data = $response->json();

        return Event::fromArray(is_array($data) ? $data : []);
    }
}
