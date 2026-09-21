<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The type of a Network member.
 *
 * - `Full`: the member belongs to the whole Network.
 * - `Limited`: the member belongs only to specific spaces.
 */
enum MemberType: string
{
    case Full = 'full';
    case Limited = 'limited';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Full => 'Full',
            self::Limited => 'Limited',
        };
    }
}
