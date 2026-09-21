<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * When a subscription cancellation should take effect.
 */
enum CancelTiming: string
{
    case Now = 'now';
    case EndOfBillingCycle = 'end_of_billing_cycle';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Now => 'Immediately',
            self::EndOfBillingCycle => 'At end of billing cycle',
        };
    }
}
