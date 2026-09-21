<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * Sort direction for Mighty API GraphQL connections (SDL `SortOrder`).
 */
enum SortOrder: string
{
    case Asc = 'ASC';
    case Desc = 'DESC';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Asc => 'Ascending',
            self::Desc => 'Descending',
        };
    }
}
