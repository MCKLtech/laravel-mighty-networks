<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

/**
 * Reusable payload selection sets for the Mighty API's mutation roots.
 *
 * Each mutation payload shares the `clientMutationId` / `errors` envelope plus
 * the changed entity. These helpers cover the entity's scalar and enum leaf
 * fields; nested connections are intentionally omitted and can be composed with
 * {@see Selection}.
 */
final class MutationSelections
{
    public const POST = 'id resourceId slug title status postType body bodyText bodyHtml contentKind '
        .'commentsEnabled cheersEnabled commentCount cheerCount answerCount isHostPost isTrending isVideo isAudio '
        .'saved url publishedAt createdAt updatedAt lastActivityAt lastEditedAt featuredIndex networkFeaturedIndex '
        .'welcomeIndex networkWelcomeIndex venue mainImageAspectRatio imageUrl viewerBlacklistType';

    public const COMMENT = 'id resourceId bodyHtml bodyText cheerCount depth replyCount replyable createdAt updatedAt url';

    public const REACTION = 'id resourceId emoji baseEmoji createdAt updatedAt';

    public const SPACE = 'id resourceId title url avatarUrl avatarDarkModeUrl discoverable explorable memberCount '
        .'unreadCount position bookmarkedAt createdAt updatedAt lastChatAt viewerNotInterested';

    public const EVENT = 'id resourceId slug title status postType body bodyText bodyHtml contentKind eventType url '
        .'startsAt endsAt location city street venue latitude longitude timeZone recurrenceFrequency recurrenceInterval '
        .'recurrenceRule isRecurring lockRsvps rsvpEnabled restrictedEvent publicExternalId hasExternalEventLink '
        .'externalEventLink externalEventLinkType postInFeed createdAt updatedAt lastActivityAt lastEditedAt '
        .'featuredIndex networkFeaturedIndex welcomeIndex networkWelcomeIndex viewerBlacklistType';

    public const RSVP = 'id resourceId status instanceAt createdAt updatedAt';

    public const WEBHOOK_CALLBACK = 'id resourceId url apiKey includedEvents disabled disabledAt consecutiveFailures createdAt updatedAt';

    public const PAYMENT_PLAN = 'id resourceId name description pitch status pricingType benefits approvalEnabled '
        .'multiple external profileQuestionsEnabled visibleToMembers taxCategory memberCount internalNotes url createdAt updatedAt';

    public const PAYMENT_SUBSCRIPTION = 'id resourceId status amount isFree paymentPlatform cancelAtPeriodEnd canceledAt '
        .'currentPeriodStart currentPeriodEnd endedAt start trialStart trialEnd gracePeriodDaysLeft completedInstallments '
        .'taxInclusive createdAt';

    public const SPACES_COLLECTION = 'id name description url avatarUrls position visibleToMembers explorable createdAt updatedAt';

    /**
     * The common mutation envelope: the changed entity plus bookkeeping fields.
     */
    public static function payload(string $entity, Selection $selection): Selection
    {
        return Selection::make()
            ->field('clientMutationId')
            ->field('errors')
            ->field($entity, selection: $selection);
    }

    /**
     * The envelope common to delete mutations: bookkeeping plus `deletedId`.
     */
    public static function deletePayload(): Selection
    {
        return Selection::make()
            ->field('clientMutationId')
            ->field('errors')
            ->field('deletedId');
    }

    /**
     * The mutation member payload, delegating to the shared member selection.
     */
    public static function member(): Selection
    {
        return Selections::member();
    }

    public static function post(): Selection
    {
        return self::leaves(self::POST);
    }

    public static function comment(): Selection
    {
        return self::leaves(self::COMMENT);
    }

    public static function reaction(): Selection
    {
        return self::leaves(self::REACTION);
    }

    public static function space(): Selection
    {
        return self::leaves(self::SPACE);
    }

    public static function event(): Selection
    {
        return self::leaves(self::EVENT);
    }

    public static function rsvp(): Selection
    {
        return self::leaves(self::RSVP);
    }

    public static function webhookCallback(): Selection
    {
        return self::leaves(self::WEBHOOK_CALLBACK);
    }

    public static function paymentPlan(): Selection
    {
        return self::leaves(self::PAYMENT_PLAN);
    }

    public static function paymentSubscription(): Selection
    {
        return self::leaves(self::PAYMENT_SUBSCRIPTION);
    }

    public static function spacesCollection(): Selection
    {
        return self::leaves(self::SPACES_COLLECTION);
    }

    /**
     * Turn a space-separated field list into a bare selection.
     */
    public static function leaves(string $fields): Selection
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
