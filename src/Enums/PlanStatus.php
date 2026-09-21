<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The lifecycle status of a paid (or free) plan.
 */
enum PlanStatus: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Archived = 'archived';
    case Legacy = 'legacy';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Visible => 'Visible',
            self::Hidden => 'Hidden',
            self::Pending => 'Pending',
            self::Rejected => 'Rejected',
            self::Archived => 'Archived',
            self::Legacy => 'Legacy',
        };
    }
}
