<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Posts;

use MCKLtech\MightyNetworks\DataTransferObjects\Post;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/posts/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetPostRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $postId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('posts/%s/', $this->postId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Post
    {
        $data = $response->json();

        return Post::fromArray(is_array($data) ? $data : []);
    }
}
