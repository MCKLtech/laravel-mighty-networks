<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The publication status of a post.
 *
 * - `Draft`: saved but not published.
 * - `Posted`: published immediately (the default).
 * - `Scheduled`: published automatically at `published_at`.
 */
enum PostStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Scheduled = 'scheduled';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::Scheduled => 'Scheduled',
        };
    }
}
