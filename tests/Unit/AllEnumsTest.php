<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\AbuseReportContextType;
use MCKLtech\MightyNetworks\Enums\AbuseReportType;
use MCKLtech\MightyNetworks\Enums\AssetStyle;
use MCKLtech\MightyNetworks\Enums\CancelTiming;
use MCKLtech\MightyNetworks\Enums\CompletionCriteria;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldPrivacy;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Enums\CustomFieldStatus;
use MCKLtech\MightyNetworks\Enums\EventFrequency;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\MemberType;
use MCKLtech\MightyNetworks\Enums\PlanStatus;
use MCKLtech\MightyNetworks\Enums\PollType;
use MCKLtech\MightyNetworks\Enums\PostStatus;
use MCKLtech\MightyNetworks\Enums\PostType;
use MCKLtech\MightyNetworks\Enums\PricingType;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\Enums\UnlockingCriteria;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exhaustively pins every enum case: its backed value and its friendly label.
 *
 * Adding or renaming a case without updating this table fails the suite, which
 * is intentional — the enums are part of the public API surface.
 */
final class AllEnumsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string, 2: string, 3: string}>
     */
    public static function enumCases(): array
    {
        $rows = [];

        $tables = [
            AbuseReportContextType::class => [
                ['Post', 'Post', 'Post'],
                ['Comment', 'Comment', 'Comment'],
                ['Space', 'Space', 'Space'],
            ],
            AbuseReportType::class => [
                ['Spam', 'spam', 'Spam'],
                ['Offensive', 'offensive', 'Offensive'],
                ['Impersonation', 'impersonation', 'Impersonation'],
                ['Other', 'other', 'Other'],
            ],
            AssetStyle::class => [
                ['Avatar', 'avatar', 'Avatar'],
                ['AvatarSuggestion', 'avatar_suggestion', 'Avatar Suggestion'],
                ['HeaderSuggestion', 'header_suggestion', 'Header Suggestion'],
                ['AvatarDarkMode', 'avatar_dark_mode', 'Avatar Dark Mode'],
                ['Thumbnail', 'thumbnail', 'Thumbnail'],
                ['SquareThumbnail', 'square_thumbnail', 'Square Thumbnail'],
                ['BundleDescription', 'bundle_description', 'Bundle Description'],
                ['LandingPageBackground', 'landing_page_background', 'Landing Page Background'],
                ['LandingPageVideo', 'landing_page_video', 'Landing Page Video'],
                ['CustomerSiteLogo', 'customer_site_logo', 'Customer Site Logo'],
                ['SpaceAvatar', 'space_avatar', 'Space Avatar'],
                ['SpaceDescription', 'space_description', 'Space Description'],
                ['BrandBannerOnColor', 'brand_banner_on_color', 'Brand Banner On Color'],
                ['BrandBannerOnWhite', 'brand_banner_on_white', 'Brand Banner On White'],
                ['BrandBannerDarkMode', 'brand_banner_dark_mode', 'Brand Banner Dark Mode'],
                ['BackgroundImageBanner', 'background_image_banner', 'Background Image Banner'],
                ['BackgroundImageBannerFixedRatio', 'background_image_banner_fixed_ratio', 'Background Image Banner Fixed Ratio'],
                ['BackgroundImageBannerFixedRatioMobile', 'background_image_banner_fixed_ratio_mobile', 'Background Image Banner Fixed Ratio Mobile'],
                ['HostHeroImage', 'host_hero_image', 'Host Hero Image'],
                ['NetworkDiscoverySubmissionHeroImage', 'network_discovery_submission_hero_image', 'Network Discovery Submission Hero Image'],
                ['NetworkDiscoverySubmissionHeroLogo', 'network_discovery_submission_hero_logo', 'Network Discovery Submission Hero Logo'],
                ['PostDescription', 'post_description', 'Post Description'],
                ['LandingPageDescription', 'landing_page_description', 'Landing Page Description'],
                ['Cover', 'cover', 'Cover'],
                ['Post', 'post', 'Post'],
                ['Comment', 'comment', 'Comment'],
                ['File', 'file', 'File'],
                ['Header', 'header', 'Header'],
                ['CinemaHeader', 'cinema_header', 'Cinema Header'],
                ['ProfilePromptAnswer', 'profile_prompt_answer', 'Profile Prompt Answer'],
                ['NewMemberPitch', 'new_member_pitch', 'New Member Pitch'],
                ['EmbeddedLink', 'embedded_link', 'Embedded Link'],
                ['UserCover', 'user_cover', 'User Cover'],
                ['Video', 'video', 'Video'],
                ['CourseVideo', 'course_video', 'Course Video'],
                ['LiveMp4Recording', 'live_mp4_recording', 'Live Mp4 Recording'],
                ['LiveMp4RecordingBackup', 'live_mp4_recording_backup', 'Live Mp4 Recording Backup'],
                ['CommentVideo', 'comment_video', 'Comment Video'],
                ['AiInteraction', 'ai_interaction', 'Ai Interaction'],
                ['AutomationActionImage', 'automation_action_image', 'Automation Action Image'],
                ['LandingPageContent', 'landing_page_content', 'Landing Page Content'],
                ['SeoImage', 'seo_image', 'Seo Image'],
                ['VoiceNote', 'voice_note', 'Voice Note'],
                ['PrimaryMedia', 'primary_media', 'Primary Media'],
                ['BodyMedia', 'body_media', 'Body Media'],
                ['StoreListingLogoVector', 'store_listing_logo_vector', 'Store Listing Logo Vector'],
                ['StoreListingIconMaster', 'store_listing_icon_master', 'Store Listing Icon Master'],
                ['StoreListingCommunityLogo', 'store_listing_community_logo', 'Store Listing Community Logo'],
                ['StoreListingMonoMark', 'store_listing_mono_mark', 'Store Listing Mono Mark'],
                ['StoreListingAdaptiveIconForeground', 'store_listing_adaptive_icon_foreground', 'Store Listing Adaptive Icon Foreground'],
                ['StoreListingSplashPhone', 'store_listing_splash_phone', 'Store Listing Splash Phone'],
                ['StoreListingSplashTablet', 'store_listing_splash_tablet', 'Store Listing Splash Tablet'],
                ['StoreListingSplashBrandingArea', 'store_listing_splash_branding_area', 'Store Listing Splash Branding Area'],
                ['StoreListingSplashBrandingTag', 'store_listing_splash_branding_tag', 'Store Listing Splash Branding Tag'],
                ['StoreListingFeatureGraphic', 'store_listing_feature_graphic', 'Store Listing Feature Graphic'],
                ['StoreListingScreenshot', 'store_listing_screenshot', 'Store Listing Screenshot'],
                ['StoreListingRendition', 'store_listing_rendition', 'Store Listing Rendition'],
            ],
            CancelTiming::class => [
                ['Now', 'now', 'Immediately'],
                ['EndOfBillingCycle', 'end_of_billing_cycle', 'At end of billing cycle'],
            ],
            CompletionCriteria::class => [
                ['None', 'none', 'None'],
                ['Visited', 'visited', 'Visited'],
                ['Button', 'button', 'Button Clicked'],
                ['Video', 'video', 'Video Watched'],
                ['MinimumCorrectPercentage', 'minimum_correct_percentage', 'Minimum Correct Percentage'],
            ],
            CourseworkStatus::class => [
                ['Posted', 'posted', 'Posted'],
                ['Hidden', 'hidden', 'Hidden'],
                ['Pending', 'pending', 'Pending'],
            ],
            CourseworkType::class => [
                ['Lesson', 'lesson', 'Lesson'],
                ['Quiz', 'quiz', 'Quiz'],
                ['Section', 'section', 'Section'],
                ['Overview', 'overview', 'Overview'],
            ],
            CustomFieldLocationGranularity::class => [
                ['Address', 'address', 'Address'],
                ['City', 'city', 'City'],
                ['State', 'state', 'State'],
                ['Country', 'country', 'Country'],
            ],
            CustomFieldPrivacy::class => [
                ['Public', 'public', 'Public'],
                ['Private', 'private', 'Private'],
            ],
            CustomFieldResponseType::class => [
                ['DropdownSingleSelect', 'dropdown_single_select', 'Dropdown (single select)'],
                ['DropdownMultiSelect', 'dropdown_multi_select', 'Dropdown (multi select)'],
                ['TextShort', 'text_short', 'Short text'],
                ['TextLong', 'text_long', 'Long text'],
                ['Number', 'number', 'Number'],
                ['Boolean', 'boolean', 'Yes/No'],
                ['Date', 'date', 'Date'],
                ['Location', 'location', 'Location'],
                ['MultiLocation', 'multi_location', 'Multiple locations'],
                ['DateRange', 'date_range', 'Date range'],
                ['RecurringDate', 'recurring_date', 'Recurring date'],
                ['Url', 'url', 'URL'],
                ['PhoneNumber', 'phone_number', 'Phone number'],
            ],
            CustomFieldStatus::class => [
                ['Visible', 'visible', 'Visible'],
                ['Hidden', 'hidden', 'Hidden'],
                ['BillingDisabled', 'billing_disabled', 'Billing disabled'],
            ],
            EventFrequency::class => [
                ['Daily', 'daily', 'Daily'],
                ['Weekly', 'weekly', 'Weekly'],
                ['Monthly', 'monthly', 'Monthly'],
                ['Yearly', 'yearly', 'Yearly'],
            ],
            MemberSort::class => [
                ['DateJoined', 'DATE_JOINED', 'Date joined'],
                ['LastVisit', 'LAST_VISIT', 'Last visit'],
                ['MemberName', 'MEMBER_NAME', 'Member name'],
                ['MemberType', 'MEMBER_TYPE', 'Member type'],
                ['ReferralCount', 'REFERRAL_COUNT', 'Referral count'],
                ['ResourceId', 'RESOURCE_ID', 'Resource ID'],
                ['Role', 'ROLE', 'Role'],
            ],
            MemberType::class => [
                ['Full', 'full', 'Full'],
                ['Limited', 'limited', 'Limited'],
            ],
            MembershipRole::class => [
                ['Host', 'host', 'Host'],
                ['Moderator', 'moderator', 'Moderator'],
                ['Contributor', 'contributor', 'Contributor'],
            ],
            PlanStatus::class => [
                ['Visible', 'visible', 'Visible'],
                ['Hidden', 'hidden', 'Hidden'],
                ['Pending', 'pending', 'Pending'],
                ['Rejected', 'rejected', 'Rejected'],
                ['Archived', 'archived', 'Archived'],
                ['Legacy', 'legacy', 'Legacy'],
            ],
            PollType::class => [
                ['MultipleChoice', 'multiple_choice', 'Multiple choice'],
                ['HotCold', 'hot_cold', 'Hot/cold'],
                ['Percentage', 'percentage', 'Percentage'],
                ['Question', 'question', 'Question'],
            ],
            PostStatus::class => [
                ['Draft', 'draft', 'Draft'],
                ['Posted', 'posted', 'Posted'],
                ['Scheduled', 'scheduled', 'Scheduled'],
            ],
            PostType::class => [
                ['Announcement', 'announcement', 'Announcement'],
                ['Article', 'article', 'Article'],
                ['Coursework', 'coursework', 'Coursework'],
                ['Event', 'event', 'Event'],
                ['EventPage', 'event_page', 'Event page'],
                ['Introduction', 'introduction', 'Introduction'],
                ['Poll', 'poll', 'Poll'],
                ['Post', 'post', 'Post'],
                ['Question', 'question', 'Question'],
                ['QuizQuestion', 'quiz_question', 'Quiz question'],
                ['SpacePage', 'space_page', 'Space page'],
            ],
            PricingType::class => [
                ['Free', 'free', 'Free'],
                ['Subscription', 'subscription', 'Subscription'],
                ['OneTime', 'one_time', 'One-time'],
                ['Installment', 'installment', 'Installment'],
                ['OneTimeInstallment', 'one_time_installment', 'One-time installment'],
                ['TokenGated', 'token_gated', 'Token gated'],
                ['NonPaid', 'nonpaid', 'Non-paid'],
            ],
            RsvpStatus::class => [
                ['Yes', 'yes', 'Yes'],
                ['Maybe', 'maybe', 'Maybe'],
                ['No', 'no', 'No'],
            ],
            SortOrder::class => [
                ['Asc', 'ASC', 'Ascending'],
                ['Desc', 'DESC', 'Descending'],
            ],
            UnlockingCriteria::class => [
                ['None', 'none', 'None'],
                ['Sequential', 'sequential', 'Sequential'],
                ['TimeFromCourseJoin', 'time_from_course_join', 'Time From Course Join'],
                ['ScheduledDate', 'scheduled_date', 'Scheduled Date'],
            ],
            WebhookEventType::class => [
                ['ArticleCreated', 'ARTICLE_CREATED', 'Article Created'],
                ['ArticleUpdated', 'ARTICLE_UPDATED', 'Article Updated'],
                ['CommentCreated', 'COMMENT_CREATED', 'Comment Created'],
                ['CustomFieldResponseCreated', 'CUSTOM_FIELD_RESPONSE_CREATED', 'Custom Field Response Created'],
                ['CustomFieldResponseRemoved', 'CUSTOM_FIELD_RESPONSE_REMOVED', 'Custom Field Response Removed'],
                ['CustomFieldResponseUpdated', 'CUSTOM_FIELD_RESPONSE_UPDATED', 'Custom Field Response Updated'],
                ['EventCanceled', 'EVENT_CANCELED', 'Event Canceled'],
                ['EventCreated', 'EVENT_CREATED', 'Event Created'],
                ['EventInstanceCanceled', 'EVENT_INSTANCE_CANCELED', 'Event Instance Canceled'],
                ['EventUpdated', 'EVENT_UPDATED', 'Event Updated'],
                ['GiftLeaderboardUpdated', 'GIFT_LEADERBOARD_UPDATED', 'Gift Leaderboard Updated'],
                ['HotColdPollCreated', 'HOT_COLD_POLL_CREATED', 'Hot Cold Poll Created'],
                ['HotColdPollUpdated', 'HOT_COLD_POLL_UPDATED', 'Hot Cold Poll Updated'],
                ['InviteRequested', 'INVITE_REQUESTED', 'Invite Requested'],
                ['MemberAmbassadorLeveledUp', 'MEMBER_AMBASSADOR_LEVELED_UP', 'Member Ambassador Leveled Up'],
                ['MemberBadgeAdded', 'MEMBER_BADGE_ADDED', 'Member Badge Added'],
                ['MemberBadgeRemoved', 'MEMBER_BADGE_REMOVED', 'Member Badge Removed'],
                ['MemberCourseProgressCompleted', 'MEMBER_COURSE_PROGRESS_COMPLETED', 'Member Course Progress Completed'],
                ['MemberCourseProgressStarted', 'MEMBER_COURSE_PROGRESS_STARTED', 'Member Course Progress Started'],
                ['MemberCourseProgressUpdated', 'MEMBER_COURSE_PROGRESS_UPDATED', 'Member Course Progress Updated'],
                ['MemberJoined', 'MEMBER_JOINED', 'Member Joined'],
                ['MemberJoinRequested', 'MEMBER_JOIN_REQUESTED', 'Member Join Requested'],
                ['MemberLeft', 'MEMBER_LEFT', 'Member Left'],
                ['MemberLessonCompleted', 'MEMBER_LESSON_COMPLETED', 'Member Lesson Completed'],
                ['MemberPlanChanged', 'MEMBER_PLAN_CHANGED', 'Member Plan Changed'],
                ['MemberPurchased', 'MEMBER_PURCHASED', 'Member Purchased'],
                ['MemberPurchaseRequested', 'MEMBER_PURCHASE_REQUESTED', 'Member Purchase Requested'],
                ['MemberRemovedFromPlan', 'MEMBER_REMOVED_FROM_PLAN', 'Member Removed From Plan'],
                ['MemberSubscriptionCanceled', 'MEMBER_SUBSCRIPTION_CANCELED', 'Member Subscription Canceled'],
                ['MemberSubscriptionRenewed', 'MEMBER_SUBSCRIPTION_RENEWED', 'Member Subscription Renewed'],
                ['MemberTagAdded', 'MEMBER_TAG_ADDED', 'Member Tag Added'],
                ['MemberTagRemoved', 'MEMBER_TAG_REMOVED', 'Member Tag Removed'],
                ['MemberUpdated', 'MEMBER_UPDATED', 'Member Updated'],
                ['MultipleChoicePollCreated', 'MULTIPLE_CHOICE_POLL_CREATED', 'Multiple Choice Poll Created'],
                ['MultipleChoicePollUpdated', 'MULTIPLE_CHOICE_POLL_UPDATED', 'Multiple Choice Poll Updated'],
                ['PercentagePollCreated', 'PERCENTAGE_POLL_CREATED', 'Percentage Poll Created'],
                ['PercentagePollUpdated', 'PERCENTAGE_POLL_UPDATED', 'Percentage Poll Updated'],
                ['PostCreated', 'POST_CREATED', 'Post Created'],
                ['PostUpdated', 'POST_UPDATED', 'Post Updated'],
                ['QuestionCreated', 'QUESTION_CREATED', 'Question Created'],
                ['QuestionUpdated', 'QUESTION_UPDATED', 'Question Updated'],
                ['ReactionCreated', 'REACTION_CREATED', 'Reaction Created'],
                ['ReactionDeleted', 'REACTION_DELETED', 'Reaction Deleted'],
                ['ReportedContentCreated', 'REPORTED_CONTENT_CREATED', 'Reported Content Created'],
                ['RsvpCreated', 'RSVP_CREATED', 'Rsvp Created'],
                ['RsvpDeleted', 'RSVP_DELETED', 'Rsvp Deleted'],
                ['RsvpUpdated', 'RSVP_UPDATED', 'Rsvp Updated'],
            ],
        ];

        foreach ($tables as $enum => $cases) {
            foreach ($cases as [$case, $value, $friendly]) {
                $short = substr($enum, strrpos($enum, '\\') + 1);
                $rows[$short.'::'.$case] = [$enum, $case, $value, $friendly];
            }
        }

        return $rows;
    }

    /**
     * @param  class-string  $enum
     */
    #[DataProvider('enumCases')]
    public function test_every_enum_case_has_its_documented_value_and_friendly_label(
        string $enum,
        string $case,
        string $value,
        string $friendly,
    ): void {
        $instance = constant($enum.'::'.$case);

        $this->assertSame($value, $instance->value);
        $this->assertSame($instance, $enum::from($value));
        $this->assertSame($friendly, $instance->toFriendly());
    }

    public function test_every_declared_enum_case_is_covered_by_the_table(): void
    {
        $rows = self::enumCases();

        $enums = [];
        foreach ($rows as [$enum, $case]) {
            $enums[$enum][] = $case;
        }

        foreach ($enums as $enum => $cases) {
            $declared = array_map(static fn ($c): string => $c->name, $enum::cases());

            sort($cases);
            sort($declared);

            $this->assertSame($declared, $cases, sprintf('%s has cases missing from the coverage table.', $enum));
        }
    }
}
