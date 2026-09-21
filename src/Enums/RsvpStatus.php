<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * A member's RSVP status for an event.
 *
 * The Admin API documents exactly three values: `yes`, `maybe`, and `no`.
 */
enum RsvpStatus: string
{
    case Yes = 'yes';
    case Maybe = 'maybe';
    case No = 'no';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Yes => 'Yes',
            self::Maybe => 'Maybe',
            self::No => 'No',
        };
    }
}
