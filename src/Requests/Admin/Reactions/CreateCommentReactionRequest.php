<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Reactions;

use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/comments/{comment_id}/reactions`
 *
 * The emoji may include skin-tone modifiers.
 */
final class CreateCommentReactionRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $commentId,
        protected readonly string $emoji,
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

    /**
     * {@inheritDoc}
     *
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return ['emoji' => $this->emoji];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Reaction
    {
        $data = $response->json();

        return Reaction::fromArray(is_array($data) ? $data : []);
    }
}
