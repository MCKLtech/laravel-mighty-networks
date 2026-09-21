<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Tags;

use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/members/{member_id}/tags`
 */
final class AddTagToMemberRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

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
        return $this->networkEndpoint(sprintf('members/%d/tags', $this->memberId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    protected function defaultBody(): array
    {
        return ['tag_id' => $this->tagId];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Tag
    {
        $data = $response->json();

        return Tag::fromArray(is_array($data) ? $data : []);
    }
}
