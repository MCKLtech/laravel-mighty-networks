<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

/**
 * Reusable selection sets for the Mighty API's core types.
 *
 * Only cheap, universally-visible leaf fields live here. Nested connections and
 * host-only fields are intentionally omitted; compose them with {@see Selection}
 * or use a raw query.
 */
final class Selections
{
    /**
     * Leaf fields of the GraphQL `Member` interface.
     */
    public const MEMBER = 'id resourceId name email firstName lastName avatarUrl shortBio timeZone '
        .'memberType networkRole isLimitedMember referralCount followedMemberCount followerCount '
        .'joinedAt lastActiveAt updatedAt url ambassadorLevel ambassadorLevelId primaryProfileFieldLabel '
        .'privateChatEnabled gamificationStreaksPublicCalendarEnabled hasPushEnabled hasConfirmedInstallation';

    /**
     * Leaf fields of the GraphQL `Network` type that a member-scoped token can read.
     */
    public const NETWORK = 'id resourceId title subtitle description slug url avatarUrl headerUrl '
        .'hostHeroImageUrl defaultLocale purpose discoverable explorable joinable createdAt updatedAt';

    /**
     * Build a selection for the `Member` interface.
     */
    public static function member(): Selection
    {
        return self::fromFieldList(self::MEMBER);
    }

    /**
     * Build a selection for the `Network` type.
     */
    public static function network(): Selection
    {
        return self::fromFieldList(self::NETWORK);
    }

    private static function fromFieldList(string $fields): Selection
    {
        $selection = Selection::make();

        foreach (explode(' ', $fields) as $field) {
            if ($field !== '') {
                $selection->field($field);
            }
        }

        return $selection;
    }
}
