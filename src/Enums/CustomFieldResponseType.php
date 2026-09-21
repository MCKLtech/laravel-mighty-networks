<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The kind of answer a custom field accepts.
 *
 * @see CustomFieldPrivacy
 * @see CustomFieldStatus
 */
enum CustomFieldResponseType: string
{
    case DropdownSingleSelect = 'dropdown_single_select';
    case DropdownMultiSelect = 'dropdown_multi_select';
    case TextShort = 'text_short';
    case TextLong = 'text_long';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Location = 'location';
    case MultiLocation = 'multi_location';
    case DateRange = 'date_range';
    case RecurringDate = 'recurring_date';
    case Url = 'url';
    case PhoneNumber = 'phone_number';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::DropdownSingleSelect => 'Dropdown (single select)',
            self::DropdownMultiSelect => 'Dropdown (multi select)',
            self::TextShort => 'Short text',
            self::TextLong => 'Long text',
            self::Number => 'Number',
            self::Boolean => 'Yes/No',
            self::Date => 'Date',
            self::Location => 'Location',
            self::MultiLocation => 'Multiple locations',
            self::DateRange => 'Date range',
            self::RecurringDate => 'Recurring date',
            self::Url => 'URL',
            self::PhoneNumber => 'Phone number',
        };
    }
}
