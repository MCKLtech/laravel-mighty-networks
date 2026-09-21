<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * A member's role within a Network.
 */
enum MembershipRole: string
{
    case Host = 'host';
    case Moderator = 'moderator';
    case Contributor = 'contributor';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Host => 'Host',
            self::Moderator => 'Moderator',
            self::Contributor => 'Contributor',
        };
    }
}
