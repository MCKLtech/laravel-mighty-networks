<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * Who may see a custom field's responses.
 */
enum CustomFieldPrivacy: string
{
    case Public = 'public';
    case Private = 'private';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
        };
    }
}
