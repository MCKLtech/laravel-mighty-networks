<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Comments;

use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/posts/{post_id}/comments/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetCommentRequest extends AdminRequest
{
    protected Method $method = Method::GET;

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

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Comment
    {
        $data = $response->json();

        return Comment::fromArray(is_array($data) ? $data : []);
    }
}
