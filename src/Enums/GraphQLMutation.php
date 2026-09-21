<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * A mutation field on the Mighty API's GraphQL `Mutation` root.
 *
 * Values are the exact camelCase operation names from the GraphQL SDL; case
 * names are the ergonomic PascalCase form. Every mutation takes a single
 * `input` argument whose type follows the `{Operation}Input` naming convention,
 * and returns a `{Operation}Payload`. Both names are derived by
 * {@see GraphQLMutation::inputType()} / {@see GraphQLMutation::payloadType()}.
 *
 * This enum is the single source of truth for mutation coverage; the test suite
 * asserts it matches the SDL bidirectionally (no missing, no extra fields).
 */
enum GraphQLMutation: string
{
    case AbortUploadSession = 'abortUploadSession';
    case ArchiveConversation = 'archiveConversation';
    case ArchivePaymentPlan = 'archivePaymentPlan';
    case BanMember = 'banMember';
    case CancelPaymentSubscription = 'cancelPaymentSubscription';
    case CompleteLiveSpace = 'completeLiveSpace';
    case CompleteQuizAttempt = 'completeQuizAttempt';
    case CompleteUploadSession = 'completeUploadSession';
    case CreateAmbassadorAnnouncement = 'createAmbassadorAnnouncement';
    case CreateAnnouncement = 'createAnnouncement';
    case CreateAnswerCheer = 'createAnswerCheer';
    case CreateAutomationRule = 'createAutomationRule';
    case CreateBadge = 'createBadge';
    case CreateBadgeMemberships = 'createBadgeMemberships';
    case CreateBlocklistEntry = 'createBlocklistEntry';
    case CreateChatReport = 'createChatReport';
    case CreateComment = 'createComment';
    case CreateCommentReport = 'createCommentReport';
    case CreateConversation = 'createConversation';
    case CreateConversationBookmark = 'createConversationBookmark';
    case CreateConversationMemberships = 'createConversationMemberships';
    case CreateCourse = 'createCourse';
    case CreateCoursework = 'createCoursework';
    case CreateCustomDomain = 'createCustomDomain';
    case CreateCustomField = 'createCustomField';
    case CreateCustomFieldOption = 'createCustomFieldOption';
    case CreateCustomOnboardingPointer = 'createCustomOnboardingPointer';
    case CreateDefaultGiftDefinitions = 'createDefaultGiftDefinitions';
    case CreateDirectMessage = 'createDirectMessage';
    case CreateDirectMessageReaction = 'createDirectMessageReaction';
    case CreateEvent = 'createEvent';
    case CreateEventAnnouncement = 'createEventAnnouncement';
    case CreateGift = 'createGift';
    case CreateGiftDefinition = 'createGiftDefinition';
    case CreateInviteRequest = 'createInviteRequest';
    case CreateInvites = 'createInvites';
    case CreateLiveSpace = 'createLiveSpace';
    case CreateMarketingBanner = 'createMarketingBanner';
    case CreateMember = 'createMember';
    case CreateMemberBlock = 'createMemberBlock';
    case CreateMemberFollow = 'createMemberFollow';
    case CreateMemberReport = 'createMemberReport';
    case CreateMessage = 'createMessage';
    case CreateMessageReaction = 'createMessageReaction';
    case CreatePaymentCoupon = 'createPaymentCoupon';
    case CreatePaymentPlan = 'createPaymentPlan';
    case CreatePaymentPlanAnnouncement = 'createPaymentPlanAnnouncement';
    case CreatePaymentPlanMembership = 'createPaymentPlanMembership';
    case CreatePaymentSubscriptionGroup = 'createPaymentSubscriptionGroup';
    case CreatePin = 'createPin';
    case CreatePoll = 'createPoll';
    case CreatePollAnswer = 'createPollAnswer';
    case CreatePost = 'createPost';
    case CreatePostMute = 'createPostMute';
    case CreatePostReport = 'createPostReport';
    case CreateProfileQuestionAnswers = 'createProfileQuestionAnswers';
    case CreatePromptMute = 'createPromptMute';
    case CreateQuizAnswer = 'createQuizAnswer';
    case CreateReaction = 'createReaction';
    case CreateRsvp = 'createRsvp';
    case CreateSavedPost = 'createSavedPost';
    case CreateSpace = 'createSpace';
    case CreateSpaceBlacklist = 'createSpaceBlacklist';
    case CreateSpaceLink = 'createSpaceLink';
    case CreateSpaceMemberships = 'createSpaceMemberships';
    case CreateSpacesCollection = 'createSpacesCollection';
    case CreateTag = 'createTag';
    case CreateTagMemberships = 'createTagMemberships';
    case CreateUploadSession = 'createUploadSession';
    case CreateWaitlist = 'createWaitlist';
    case CreateWebhookCallback = 'createWebhookCallback';
    case DeleteAnswerCheer = 'deleteAnswerCheer';
    case DeleteAsset = 'deleteAsset';
    case DeleteAutomationRule = 'deleteAutomationRule';
    case DeleteBadge = 'deleteBadge';
    case DeleteBadgeMemberships = 'deleteBadgeMemberships';
    case DeleteBlocklistEntry = 'deleteBlocklistEntry';
    case DeleteChatReport = 'deleteChatReport';
    case DeleteComment = 'deleteComment';
    case DeleteCommentReport = 'deleteCommentReport';
    case DeleteConversationBookmark = 'deleteConversationBookmark';
    case DeleteConversationMemberships = 'deleteConversationMemberships';
    case DeleteCourse = 'deleteCourse';
    case DeleteCoursework = 'deleteCoursework';
    case DeleteCustomDomain = 'deleteCustomDomain';
    case DeleteCustomField = 'deleteCustomField';
    case DeleteCustomFieldAnswer = 'deleteCustomFieldAnswer';
    case DeleteCustomFieldOption = 'deleteCustomFieldOption';
    case DeleteCustomOnboardingPointer = 'deleteCustomOnboardingPointer';
    case DeleteDirectMessage = 'deleteDirectMessage';
    case DeleteDirectMessageReaction = 'deleteDirectMessageReaction';
    case DeleteEvent = 'deleteEvent';
    case DeleteEventInstance = 'deleteEventInstance';
    case DeleteEventRecurrenceException = 'deleteEventRecurrenceException';
    case DeleteGiftDefinition = 'deleteGiftDefinition';
    case DeleteMarketingBanner = 'deleteMarketingBanner';
    case DeleteMember = 'deleteMember';
    case DeleteMemberBlock = 'deleteMemberBlock';
    case DeleteMemberFollow = 'deleteMemberFollow';
    case DeleteMessage = 'deleteMessage';
    case DeleteMessageReaction = 'deleteMessageReaction';
    case DeleteNetworkMembership = 'deleteNetworkMembership';
    case DeletePaymentPlanMembership = 'deletePaymentPlanMembership';
    case DeletePaymentSubscriptionGroup = 'deletePaymentSubscriptionGroup';
    case DeletePin = 'deletePin';
    case DeletePost = 'deletePost';
    case DeletePostMute = 'deletePostMute';
    case DeletePostReport = 'deletePostReport';
    case DeletePromptMute = 'deletePromptMute';
    case DeleteReaction = 'deleteReaction';
    case DeleteRsvp = 'deleteRsvp';
    case DeleteSavedPost = 'deleteSavedPost';
    case DeleteSpace = 'deleteSpace';
    case DeleteSpaceBlacklist = 'deleteSpaceBlacklist';
    case DeleteSpaceLink = 'deleteSpaceLink';
    case DeleteSpaceMembership = 'deleteSpaceMembership';
    case DeleteSpacesCollection = 'deleteSpacesCollection';
    case DeleteTag = 'deleteTag';
    case DeleteTagMemberships = 'deleteTagMemberships';
    case DeleteWaitlist = 'deleteWaitlist';
    case DeleteWebhookCallback = 'deleteWebhookCallback';
    case LeaveConversation = 'leaveConversation';
    case MarkAllConversationsRead = 'markAllConversationsRead';
    case MarkConversationRead = 'markConversationRead';
    case MarkDirectMessageRead = 'markDirectMessageRead';
    case MoveSpaceToCollection = 'moveSpaceToCollection';
    case PauseAutomationRule = 'pauseAutomationRule';
    case PublishCoursework = 'publishCoursework';
    case PublishCustomDomain = 'publishCustomDomain';
    case PublishPaymentPlan = 'publishPaymentPlan';
    case ReorderCoursework = 'reorderCoursework';
    case ReorderGalleryGroup = 'reorderGalleryGroup';
    case ReorderGiftDefinition = 'reorderGiftDefinition';
    case ReorderPin = 'reorderPin';
    case ReorderSpace = 'reorderSpace';
    case ReorderSpacesCollection = 'reorderSpacesCollection';
    case ResendInvite = 'resendInvite';
    case RevokeInvite = 'revokeInvite';
    case UnpublishCoursework = 'unpublishCoursework';
    case UnpublishPaymentPlan = 'unpublishPaymentPlan';
    case UpdateAutomationRule = 'updateAutomationRule';
    case UpdateBadge = 'updateBadge';
    case UpdateComment = 'updateComment';
    case UpdateConversation = 'updateConversation';
    case UpdateCoursework = 'updateCoursework';
    case UpdateCourseworkProgress = 'updateCourseworkProgress';
    case UpdateCustomField = 'updateCustomField';
    case UpdateCustomFieldAnswer = 'updateCustomFieldAnswer';
    case UpdateCustomFieldOption = 'updateCustomFieldOption';
    case UpdateCustomOnboardingPointer = 'updateCustomOnboardingPointer';
    case UpdateEvent = 'updateEvent';
    case UpdateEventInstance = 'updateEventInstance';
    case UpdateGalleryGroup = 'updateGalleryGroup';
    case UpdateGalleryGroups = 'updateGalleryGroups';
    case UpdateGiftDefinition = 'updateGiftDefinition';
    case UpdateInviteRequest = 'updateInviteRequest';
    case UpdateInviteRequests = 'updateInviteRequests';
    case UpdateMarketingBanner = 'updateMarketingBanner';
    case UpdateMember = 'updateMember';
    case UpdateMemberReports = 'updateMemberReports';
    case UpdateMembership = 'updateMembership';
    case UpdateNetwork = 'updateNetwork';
    case UpdateNetworkImage = 'updateNetworkImage';
    case UpdateNetworkLandingSpace = 'updateNetworkLandingSpace';
    case UpdateNetworkSubdomain = 'updateNetworkSubdomain';
    case UpdateNetworkTheme = 'updateNetworkTheme';
    case UpdateNotification = 'updateNotification';
    case UpdateNotificationSetting = 'updateNotificationSetting';
    case UpdatePaymentCoupon = 'updatePaymentCoupon';
    case UpdatePaymentPlan = 'updatePaymentPlan';
    case UpdatePaymentPlanProfileQuestions = 'updatePaymentPlanProfileQuestions';
    case UpdatePaymentSubscriptionGroup = 'updatePaymentSubscriptionGroup';
    case UpdatePoll = 'updatePoll';
    case UpdatePost = 'updatePost';
    case UpdateRsvp = 'updateRsvp';
    case UpdateSpace = 'updateSpace';
    case UpdateSpaceImage = 'updateSpaceImage';
    case UpdateSpaceLink = 'updateSpaceLink';
    case UpdateSpaceProfileQuestions = 'updateSpaceProfileQuestions';
    case UpdateSpacesCollection = 'updateSpacesCollection';
    case UpdateTag = 'updateTag';
    case UpdateVideoProgress = 'updateVideoProgress';
    case UpdateWaitlist = 'updateWaitlist';
    case UpdateWebhookCallback = 'updateWebhookCallback';
    case VoidPurchase = 'voidPurchase';

    /**
     * The GraphQL input type required by this mutation, e.g. `CreateMemberInput`.
     */
    public function inputType(): string
    {
        return ucfirst($this->value).'Input';
    }

    /**
     * The GraphQL payload type returned by this mutation, e.g. `CreateMemberPayload`.
     */
    public function payloadType(): string
    {
        return ucfirst($this->value).'Payload';
    }

    /**
     * The PascalCase operation name used in the document header, e.g. `CreateMember`.
     */
    public function operationName(): string
    {
        return ucfirst($this->value);
    }

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        $snake = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $this->value);

        return ucfirst(is_string($snake) ? $snake : $this->value);
    }

    /**
     * Resolve a mutation from any of the operation spellings a caller might
     * supply (`createMember`, `CreateMember`, `CREATE_MEMBER`). Returns null for
     * an unknown or empty name so callers never silently build an invalid
     * document from a typo.
     */
    public static function fromOperationName(string $name): ?self
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $exact = self::tryFrom($name);

        if ($exact !== null) {
            return $exact;
        }

        $snake = preg_replace(
            '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/',
            '_',
            $name,
        );

        if (! is_string($snake)) {
            return null;
        }

        $camel = lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace(['_', '-'], ' ', $snake)))));

        return self::tryFrom($camel);
    }
}
