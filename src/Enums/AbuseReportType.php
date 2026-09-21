<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The category of an abuse report.
 */
enum AbuseReportType: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case Impersonation = 'impersonation';
    case Other = 'other';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Offensive => 'Offensive',
            self::Impersonation => 'Impersonation',
            self::Other => 'Other',
        };
    }
}
