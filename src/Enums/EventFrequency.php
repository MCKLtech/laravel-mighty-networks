<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The recurrence frequency of an event.
 *
 * The Admin API documents exactly four values: `daily`, `weekly`, `monthly`,
 * and `yearly`.
 */
enum EventFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }
}
