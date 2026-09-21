<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomField;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCustomFieldData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/custom_fields/{id}/`
 *
 * Replaces the custom field with the supplied representation.
 */
final class ReplaceCustomFieldRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $id,
        protected readonly UpdateCustomFieldData $data,
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
    public function createDtoFromResponse(Response $response): CustomField
    {
        $data = $response->json();

        return CustomField::fromArray(is_array($data) ? $data : []);
    }
}
