<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomField;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/custom_fields/{id}/`
 */
final class GetCustomFieldRequest extends AdminRequest
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
        return $this->networkEndpoint(sprintf('custom_fields/%d/', $this->id));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): CustomField
    {
        $data = $response->json();

        return CustomField::fromArray(is_array($data) ? $data : []);
    }
}
