<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/spaces/{space_id}/members/{user_id}/ban`
 *
 * Bans a user from the entire Network. Returns an empty object on success.
 */
final class BanSpaceMemberRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly int $userId,
        protected readonly ?string $banReason = null,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('spaces/%d/members/%d/ban', $this->spaceId, $this->userId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            ['ban_reason' => $this->banReason],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
