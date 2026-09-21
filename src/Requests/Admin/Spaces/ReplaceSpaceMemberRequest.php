<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/spaces/{space_id}/members/{user_id}/`
 *
 * Replaces a member's role (and optionally profile fields) within the space.
 */
final class ReplaceSpaceMemberRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly int $userId,
        protected readonly UpdateMemberData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('spaces/%d/members/%d/', $this->spaceId, $this->userId));
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
    public function createDtoFromResponse(Response $response): Member
    {
        $data = $response->json();

        return Member::fromArray(is_array($data) ? $data : []);
    }
}
