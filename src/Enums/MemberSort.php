<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * Sort key for the Mighty API GraphQL member roster (SDL `MemberSort`).
 *
 * `RESOURCE_ID` is the only key that never changes for a member, so it is the
 * one that guarantees a complete enumeration returns every member exactly once.
 */
enum MemberSort: string
{
    case DateJoined = 'DATE_JOINED';
    case LastVisit = 'LAST_VISIT';
    case MemberName = 'MEMBER_NAME';
    case MemberType = 'MEMBER_TYPE';
    case ReferralCount = 'REFERRAL_COUNT';
    case ResourceId = 'RESOURCE_ID';
    case Role = 'ROLE';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::DateJoined => 'Date joined',
            self::LastVisit => 'Last visit',
            self::MemberName => 'Member name',
            self::MemberType => 'Member type',
            self::ReferralCount => 'Referral count',
            self::ResourceId => 'Resource ID',
            self::Role => 'Role',
        };
    }
}
