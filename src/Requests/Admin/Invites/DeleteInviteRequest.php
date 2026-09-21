<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Invites;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/invites/{id}/`
 *
 * Deletes an unaccepted invite.
 */
final class DeleteInviteRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $inviteId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('invites/%s/', $this->inviteId));
    }
}
