<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Tags;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/members/{member_id}/tags/{tag_id}/`
 */
final class RemoveTagFromMemberRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $memberId,
        protected readonly int $tagId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('members/%d/tags/%d/', $this->memberId, $this->tagId));
    }
}
