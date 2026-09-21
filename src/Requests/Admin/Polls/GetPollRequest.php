<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Polls;

use MCKLtech\MightyNetworks\DataTransferObjects\Poll;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/polls/{id}/`
 */
final class GetPollRequest extends AdminRequest
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
        return $this->networkEndpoint(sprintf('polls/%d/', $this->id));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Poll
    {
        $data = $response->json();

        return Poll::fromArray(is_array($data) ? $data : []);
    }
}
