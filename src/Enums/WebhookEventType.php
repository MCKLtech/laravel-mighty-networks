<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * An event a Mighty Networks webhook callback can subscribe to.
 *
 * The enum values are the exact UPPER_SNAKE values from the GraphQL SDL. The
 * delivery envelope is inconsistent in practice, however: the same event may
 * arrive as `POST_CREATED`, `PostCreated` or as a GraphQL payload type such as
 * `MemberJoinedHook`. Use {@see WebhookEventType::fromEventName()} to normalise
 * any of those spellings before matching.
 */
enum WebhookEventType: string
{
    case ArticleCreated = 'ARTICLE_CREATED';
    case ArticleUpdated = 'ARTICLE_UPDATED';
    case CommentCreated = 'COMMENT_CREATED';
    case CustomFieldResponseCreated = 'CUSTOM_FIELD_RESPONSE_CREATED';
    case CustomFieldResponseRemoved = 'CUSTOM_FIELD_RESPONSE_REMOVED';
    case CustomFieldResponseUpdated = 'CUSTOM_FIELD_RESPONSE_UPDATED';
    case EventCanceled = 'EVENT_CANCELED';
    case EventCreated = 'EVENT_CREATED';
    case EventInstanceCanceled = 'EVENT_INSTANCE_CANCELED';
    case EventUpdated = 'EVENT_UPDATED';
    case GiftLeaderboardUpdated = 'GIFT_LEADERBOARD_UPDATED';
    case HotColdPollCreated = 'HOT_COLD_POLL_CREATED';
    case HotColdPollUpdated = 'HOT_COLD_POLL_UPDATED';
    case InviteRequested = 'INVITE_REQUESTED';
    case MemberAmbassadorLeveledUp = 'MEMBER_AMBASSADOR_LEVELED_UP';
    case MemberBadgeAdded = 'MEMBER_BADGE_ADDED';
    case MemberBadgeRemoved = 'MEMBER_BADGE_REMOVED';
    case MemberCourseProgressCompleted = 'MEMBER_COURSE_PROGRESS_COMPLETED';
    case MemberCourseProgressStarted = 'MEMBER_COURSE_PROGRESS_STARTED';
    case MemberCourseProgressUpdated = 'MEMBER_COURSE_PROGRESS_UPDATED';
    case MemberJoined = 'MEMBER_JOINED';
    case MemberJoinRequested = 'MEMBER_JOIN_REQUESTED';
    case MemberLeft = 'MEMBER_LEFT';
    case MemberLessonCompleted = 'MEMBER_LESSON_COMPLETED';
    case MemberPlanChanged = 'MEMBER_PLAN_CHANGED';
    case MemberPurchased = 'MEMBER_PURCHASED';
    case MemberPurchaseRequested = 'MEMBER_PURCHASE_REQUESTED';
    case MemberRemovedFromPlan = 'MEMBER_REMOVED_FROM_PLAN';
    case MemberSubscriptionCanceled = 'MEMBER_SUBSCRIPTION_CANCELED';
    case MemberSubscriptionRenewed = 'MEMBER_SUBSCRIPTION_RENEWED';
    case MemberTagAdded = 'MEMBER_TAG_ADDED';
    case MemberTagRemoved = 'MEMBER_TAG_REMOVED';
    case MemberUpdated = 'MEMBER_UPDATED';
    case MultipleChoicePollCreated = 'MULTIPLE_CHOICE_POLL_CREATED';
    case MultipleChoicePollUpdated = 'MULTIPLE_CHOICE_POLL_UPDATED';
    case PercentagePollCreated = 'PERCENTAGE_POLL_CREATED';
    case PercentagePollUpdated = 'PERCENTAGE_POLL_UPDATED';
    case PostCreated = 'POST_CREATED';
    case PostUpdated = 'POST_UPDATED';
    case QuestionCreated = 'QUESTION_CREATED';
    case QuestionUpdated = 'QUESTION_UPDATED';
    case ReactionCreated = 'REACTION_CREATED';
    case ReactionDeleted = 'REACTION_DELETED';
    case ReportedContentCreated = 'REPORTED_CONTENT_CREATED';
    case RsvpCreated = 'RSVP_CREATED';
    case RsvpDeleted = 'RSVP_DELETED';
    case RsvpUpdated = 'RSVP_UPDATED';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    /**
     * Resolve an event type from any of the name spellings Mighty Networks uses
     * (`PostCreated`, `POST_CREATED`, `MemberJoinedHook`). Returns null for an
     * unknown or empty name so callers never have to throw on new event types.
     */
    public static function fromEventName(string $name): ?self
    {
        $normalised = self::normaliseName($name);

        return $normalised === null ? null : self::tryFrom($normalised);
    }

    /**
     * Normalise an event name to the UPPER_SNAKE form used by the enum.
     */
    private static function normaliseName(string $name): ?string
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        // GraphQL payload types append a "Hook" suffix (e.g. MemberJoinedHook).
        if (str_ends_with($name, 'Hook')) {
            $name = substr($name, 0, -4);
        }

        // Split CamelCase/PascalCase into words, then upper-case and join.
        $snake = preg_replace(
            '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/',
            '_',
            $name,
        );

        if (! is_string($snake)) {
            return null;
        }

        $normalised = strtoupper(str_replace(['-', ' '], '_', $snake));

        return $normalised === '' ? null : $normalised;
    }
}
