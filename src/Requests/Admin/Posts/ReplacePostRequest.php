<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Posts;

use MCKLtech\MightyNetworks\DataTransferObjects\Post;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePostData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/posts/{id}/`
 *
 * A full replace of the post or article. The optional `notify` query parameter
 * controls whether the Network is notified about the change.
 */
final class ReplacePostRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $postId,
        protected readonly UpdatePostData $data,
        protected readonly ?bool $notify = null,
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
     *
     * @return array<string, bool>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return $this->notify === null ? [] : ['notify' => $this->notify];
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
    public function createDtoFromResponse(Response $response): Post
    {
        $data = $response->json();

        return Post::fromArray(is_array($data) ? $data : []);
    }
}
