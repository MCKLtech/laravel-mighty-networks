<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The level of detail members pick a place at on a location custom field.
 *
 * This is fixed once the field exists.
 */
enum CustomFieldLocationGranularity: string
{
    case Address = 'address';
    case City = 'city';
    case State = 'state';
    case Country = 'country';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Address => 'Address',
            self::City => 'City',
            self::State => 'State',
            self::Country => 'Country',
        };
    }
}
