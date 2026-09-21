<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Badges;

use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/members/{member_id}/badges`
 */
final class AddBadgeToMemberRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $memberId,
        protected readonly int $badgeId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('members/%d/badges', $this->memberId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    protected function defaultBody(): array
    {
        return ['badge_id' => $this->badgeId];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Badge
    {
        $data = $response->json();

        return Badge::fromArray(is_array($data) ? $data : []);
    }
}
