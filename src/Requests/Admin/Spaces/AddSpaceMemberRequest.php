<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `POST networks/{network_id}/spaces/{space_id}/members?user_id=...`
 *
 * Adds an existing Network user to a space. The user ID travels as a required
 * query parameter, per the spec.
 */
final class AddSpaceMemberRequest extends AdminRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
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
        return $this->networkEndpoint(sprintf('spaces/%d/members', $this->spaceId));
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
