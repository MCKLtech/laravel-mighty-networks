<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The type of context an abuse report was made in.
 *
 * The API documents these as capitalised resource names (`Post`, `Comment`,
 * `Space`), so the backed values match that casing verbatim.
 */
enum AbuseReportContextType: string
{
    case Post = 'Post';
    case Comment = 'Comment';
    case Space = 'Space';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return $this->value;
    }
}
