<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * The authorization context returned by the Admin REST `me` endpoint: the
 * Network the token belongs to plus the authenticated user.
 */
final readonly class Me
{
    public function __construct(
        public Network $network,
        public NetworkUser $user,
    ) {}

    /**
     * Create a Me from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $network = $data['network'] ?? null;
        $user = $data['user'] ?? null;

        return new self(
            network: Network::fromArray(is_array($network) ? $network : []),
            user: NetworkUser::fromArray(is_array($user) ? $user : []),
        );
    }
}
