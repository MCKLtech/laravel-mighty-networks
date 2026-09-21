<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The visibility status of a coursework item.
 */
enum CourseworkStatus: string
{
    case Posted = 'posted';
    case Hidden = 'hidden';
    case Pending = 'pending';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Posted => 'Posted',
            self::Hidden => 'Hidden',
            self::Pending => 'Pending',
        };
    }
}
