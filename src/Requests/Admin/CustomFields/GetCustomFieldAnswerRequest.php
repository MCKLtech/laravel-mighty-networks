<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldAnswer;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/custom_fields/{custom_field_id}/members/{member_id}/answers/{id}/`
 */
final class GetCustomFieldAnswerRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $customFieldId,
        protected readonly int $memberId,
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
        return $this->networkEndpoint(sprintf(
            'custom_fields/%d/members/%d/answers/%d/',
            $this->customFieldId,
            $this->memberId,
            $this->id,
        ));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): CustomFieldAnswer
    {
        $data = $response->json();

        return CustomFieldAnswer::fromArray(is_array($data) ? $data : []);
    }
}
