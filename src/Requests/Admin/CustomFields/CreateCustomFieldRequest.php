<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomField;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/custom_fields`
 */
final class CreateCustomFieldRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly NewCustomFieldData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('custom_fields');
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
