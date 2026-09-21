<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Badges;

use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateBadgeData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/badges/{id}/`
 *
 * Replaces the badge with the supplied representation.
 */
final class ReplaceBadgeRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $id,
        protected readonly UpdateBadgeData $data,
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
    public function createDtoFromResponse(Response $response): Badge
    {
        $data = $response->json();

        return Badge::fromArray(is_array($data) ? $data : []);
    }
}
