<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\AssetStyle;
use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldPrivacy;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Enums\CustomFieldStatus;
use MCKLtech\MightyNetworks\Enums\PollType;
use PHPUnit\Framework\TestCase;

final class CatalogEnumsTest extends TestCase
{
    public function test_custom_field_response_type_has_backed_values(): void
    {
        $this->assertSame('dropdown_single_select', CustomFieldResponseType::DropdownSingleSelect->value);
        $this->assertSame('dropdown_multi_select', CustomFieldResponseType::DropdownMultiSelect->value);
        $this->assertSame('text_short', CustomFieldResponseType::TextShort->value);
        $this->assertSame('text_long', CustomFieldResponseType::TextLong->value);
        $this->assertSame('number', CustomFieldResponseType::Number->value);
        $this->assertSame('boolean', CustomFieldResponseType::Boolean->value);
        $this->assertSame('date', CustomFieldResponseType::Date->value);
        $this->assertSame('location', CustomFieldResponseType::Location->value);
        $this->assertSame('multi_location', CustomFieldResponseType::MultiLocation->value);
        $this->assertSame('date_range', CustomFieldResponseType::DateRange->value);
        $this->assertSame('recurring_date', CustomFieldResponseType::RecurringDate->value);
        $this->assertSame('url', CustomFieldResponseType::Url->value);
        $this->assertSame('phone_number', CustomFieldResponseType::PhoneNumber->value);
        $this->assertSame('Short text', CustomFieldResponseType::TextShort->toFriendly());
    }

    public function test_custom_field_privacy_and_status_have_backed_values(): void
    {
        $this->assertSame('public', CustomFieldPrivacy::Public->value);
        $this->assertSame('private', CustomFieldPrivacy::Private->value);
        $this->assertSame('Private', CustomFieldPrivacy::Private->toFriendly());

        $this->assertSame('visible', CustomFieldStatus::Visible->value);
        $this->assertSame('hidden', CustomFieldStatus::Hidden->value);
        $this->assertSame('billing_disabled', CustomFieldStatus::BillingDisabled->value);
        $this->assertSame('Billing disabled', CustomFieldStatus::BillingDisabled->toFriendly());
    }

    public function test_location_granularity_and_poll_type_have_backed_values(): void
    {
        $this->assertSame('address', CustomFieldLocationGranularity::Address->value);
        $this->assertSame('city', CustomFieldLocationGranularity::City->value);
        $this->assertSame('state', CustomFieldLocationGranularity::State->value);
        $this->assertSame('country', CustomFieldLocationGranularity::Country->value);

        $this->assertSame('multiple_choice', PollType::MultipleChoice->value);
        $this->assertSame('hot_cold', PollType::HotCold->value);
        $this->assertSame('percentage', PollType::Percentage->value);
        $this->assertSame('question', PollType::Question->value);
        $this->assertSame('Multiple choice', PollType::MultipleChoice->toFriendly());
    }

    public function test_asset_style_has_backed_values(): void
    {
        $this->assertSame('avatar', AssetStyle::Avatar->value);
        $this->assertSame('landing_page_video', AssetStyle::LandingPageVideo->value);
        $this->assertSame('file', AssetStyle::File->value);
        $this->assertSame('Store Listing Screenshot', AssetStyle::StoreListingScreenshot->toFriendly());
    }
}
