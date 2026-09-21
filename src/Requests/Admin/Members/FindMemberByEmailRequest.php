<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Members;

use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/members/by_email?email=...`
 *
 * The headline helper for looking a member up without knowing their ID.
 */
final class FindMemberByEmailRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly string $email,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('members/by_email');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return ['email' => $this->email];
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
