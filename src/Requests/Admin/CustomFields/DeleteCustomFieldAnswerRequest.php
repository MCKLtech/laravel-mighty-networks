<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/custom_fields/{custom_field_id}/members/{member_id}/answers`
 */
final class DeleteCustomFieldAnswerRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $customFieldId,
        protected readonly int $memberId,
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
            'custom_fields/%d/members/%d/answers',
            $this->customFieldId,
            $this->memberId,
        ));
    }
}
