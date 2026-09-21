<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Posts;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `POST networks/{network_id}/posts/{post_id}/mute?user_id=...`
 *
 * Mute a post for a specific user (unfollow notifications).
 */
final class MutePostRequest extends AdminRequest
{
    protected Method $method = Method::POST;

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
