<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Members;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `POST networks/{network_id}/members/{member_id}/password_resets`
 */
final class SendPasswordResetRequest extends AdminRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
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
        return $this->networkEndpoint(sprintf('members/%s/password_resets', $this->memberId));
    }
}
