<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldOption;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCustomFieldOptionData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/custom_fields/{custom_field_id}/options`
 */
final class CreateCustomFieldOptionRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $customFieldId,
        protected readonly NewCustomFieldOptionData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('custom_fields/%d/options', $this->customFieldId));
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
    public function createDtoFromResponse(Response $response): CustomFieldOption
    {
        $data = $response->json();

        return CustomFieldOption::fromArray(is_array($data) ? $data : []);
    }
}
