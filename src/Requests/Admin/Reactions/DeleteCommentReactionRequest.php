<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Reactions;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/comments/{comment_id}/reactions`
 *
 * The endpoint takes no reaction identifier; it removes the caller's reaction
 * from the comment.
 */
final class DeleteCommentReactionRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $commentId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('comments/%s/reactions', $this->commentId));
    }
}
