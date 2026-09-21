<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Comments;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/posts/{post_id}/comments/{id}/`
 */
final class DeleteCommentRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $postId,
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
        return $this->networkEndpoint(sprintf('posts/%s/comments/%s/', $this->postId, $this->commentId));
    }
}
