<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The visibility status of an existing custom field.
 */
enum CustomFieldStatus: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';
    case BillingDisabled = 'billing_disabled';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Visible => 'Visible',
            self::Hidden => 'Hidden',
            self::BillingDisabled => 'Billing disabled',
        };
    }
}
