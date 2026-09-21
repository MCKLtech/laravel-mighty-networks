<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The style/purpose of an uploaded asset.
 *
 * The Admin API accepts any of these on `POST assets` as `asset_style`.
 */
enum AssetStyle: string
{
    case Avatar = 'avatar';
    case AvatarSuggestion = 'avatar_suggestion';
    case HeaderSuggestion = 'header_suggestion';
    case AvatarDarkMode = 'avatar_dark_mode';
    case Thumbnail = 'thumbnail';
    case SquareThumbnail = 'square_thumbnail';
    case BundleDescription = 'bundle_description';
    case LandingPageBackground = 'landing_page_background';
    case LandingPageVideo = 'landing_page_video';
    case CustomerSiteLogo = 'customer_site_logo';
    case SpaceAvatar = 'space_avatar';
    case SpaceDescription = 'space_description';
    case BrandBannerOnColor = 'brand_banner_on_color';
    case BrandBannerOnWhite = 'brand_banner_on_white';
    case BrandBannerDarkMode = 'brand_banner_dark_mode';
    case BackgroundImageBanner = 'background_image_banner';
    case BackgroundImageBannerFixedRatio = 'background_image_banner_fixed_ratio';
    case BackgroundImageBannerFixedRatioMobile = 'background_image_banner_fixed_ratio_mobile';
    case HostHeroImage = 'host_hero_image';
    case NetworkDiscoverySubmissionHeroImage = 'network_discovery_submission_hero_image';
    case NetworkDiscoverySubmissionHeroLogo = 'network_discovery_submission_hero_logo';
    case PostDescription = 'post_description';
    case LandingPageDescription = 'landing_page_description';
    case Cover = 'cover';
    case Post = 'post';
    case Comment = 'comment';
    case File = 'file';
    case Header = 'header';
    case CinemaHeader = 'cinema_header';
    case ProfilePromptAnswer = 'profile_prompt_answer';
    case NewMemberPitch = 'new_member_pitch';
    case EmbeddedLink = 'embedded_link';
    case UserCover = 'user_cover';
    case Video = 'video';
    case CourseVideo = 'course_video';
    case LiveMp4Recording = 'live_mp4_recording';
    case LiveMp4RecordingBackup = 'live_mp4_recording_backup';
    case CommentVideo = 'comment_video';
    case AiInteraction = 'ai_interaction';
    case AutomationActionImage = 'automation_action_image';
    case LandingPageContent = 'landing_page_content';
    case SeoImage = 'seo_image';
    case VoiceNote = 'voice_note';
    case PrimaryMedia = 'primary_media';
    case BodyMedia = 'body_media';
    case StoreListingLogoVector = 'store_listing_logo_vector';
    case StoreListingIconMaster = 'store_listing_icon_master';
    case StoreListingCommunityLogo = 'store_listing_community_logo';
    case StoreListingMonoMark = 'store_listing_mono_mark';
    case StoreListingAdaptiveIconForeground = 'store_listing_adaptive_icon_foreground';
    case StoreListingSplashPhone = 'store_listing_splash_phone';
    case StoreListingSplashTablet = 'store_listing_splash_tablet';
    case StoreListingSplashBrandingArea = 'store_listing_splash_branding_area';
    case StoreListingSplashBrandingTag = 'store_listing_splash_branding_tag';
    case StoreListingFeatureGraphic = 'store_listing_feature_graphic';
    case StoreListingScreenshot = 'store_listing_screenshot';
    case StoreListingRendition = 'store_listing_rendition';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
