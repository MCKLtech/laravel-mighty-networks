<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Posts;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/posts/{post_id}/mute?user_id=...`
 *
 * Unmute a post for a specific user (refollow notifications).
 */
final class UnmutePostRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $postId,
        protected readonly int $userId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('posts/%s/mute', $this->postId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return ['user_id' => $this->userId];
    }
}
