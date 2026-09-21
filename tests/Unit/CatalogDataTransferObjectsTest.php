<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Collections\BadgeCollection;
use MCKLtech\MightyNetworks\Collections\CustomFieldAnswerCollection;
use MCKLtech\MightyNetworks\Collections\CustomFieldCollection;
use MCKLtech\MightyNetworks\Collections\CustomFieldOptionCollection;
use MCKLtech\MightyNetworks\Collections\PollCollection;
use MCKLtech\MightyNetworks\Collections\TagCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Asset;
use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomField;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldAnswer;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldOption;
use MCKLtech\MightyNetworks\DataTransferObjects\NewBadgeData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPollData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewTagData;
use MCKLtech\MightyNetworks\DataTransferObjects\Poll;
use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateBadgeData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePollData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateTagData;
use MCKLtech\MightyNetworks\Enums\CustomFieldLocationGranularity;
use MCKLtech\MightyNetworks\Enums\CustomFieldResponseType;
use MCKLtech\MightyNetworks\Enums\PollType;
use PHPUnit\Framework\TestCase;

final class CatalogDataTransferObjectsTest extends TestCase
{
    public function test_tag_from_array(): void
    {
        $tag = Tag::fromArray([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'VIP Member',
            'description' => 'Premium tier members',
            'color' => '#FF5733',
            'custom_field_id' => 99,
        ]);

        $this->assertSame(7, $tag->id);
        $this->assertSame('VIP Member', $tag->title);
        $this->assertSame('#FF5733', $tag->color);
        $this->assertSame(99, $tag->customFieldId);
        $this->assertSame('2024-01-15T10:30:00+00:00', $tag->createdAt->toIso8601String());
    }

    public function test_badge_from_array(): void
    {
        $badge = Badge::fromArray([
            'id' => 8,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Top Contributor',
            'description' => 'Awarded for exceptional contributions',
            'color' => '#FFD700',
            'custom_field_id' => 100,
            'avatar_url' => 'https://cdn.mn.co/badges/8.png',
        ]);

        $this->assertSame(8, $badge->id);
        $this->assertSame('Top Contributor', $badge->title);
        $this->assertSame('https://cdn.mn.co/badges/8.png', $badge->avatarUrl);
        $this->assertSame(100, $badge->customFieldId);
    }

    public function test_custom_field_from_array_maps_enums(): void
    {
        $field = CustomField::fromArray([
            'id' => 3,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Location',
            'response_type' => 'multi_location',
            'location_granularity' => 'city',
        ]);

        $this->assertSame(CustomFieldResponseType::MultiLocation, $field->responseType);
        $this->assertSame(CustomFieldLocationGranularity::City, $field->locationGranularity);
    }

    public function test_custom_field_option_from_array(): void
    {
        $option = CustomFieldOption::fromArray([
            'id' => 4,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'title' => 'Blue',
            'description' => 'A calming colour',
            'custom_field_id' => 3,
            'member_count' => 12,
            'is_ad_hoc' => true,
            'creator_id' => 42,
        ]);

        $this->assertSame('Blue', $option->title);
        $this->assertSame(12, $option->memberCount);
        $this->assertTrue($option->isAdHoc);
        $this->assertSame(42, $option->creatorId);
    }

    public function test_custom_field_answer_from_array_single_row_shape(): void
    {
        $answer = CustomFieldAnswer::fromArray([
            'id' => 5,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'last_edited_at' => '2024-03-21T09:00:00+00:00',
            'user_id' => 42,
            'custom_field_id' => 3,
            'text' => 'I love hiking',
            'number' => 42,
            'boolean_value' => true,
            'segments' => [['id' => 1, 'title' => 'Founders']],
        ]);

        $this->assertSame(5, $answer->id);
        $this->assertSame(42, $answer->userId);
        $this->assertSame('I love hiking', $answer->text);
        $this->assertSame(42, $answer->number);
        $this->assertTrue($answer->booleanValue);
        $this->assertSame([['id' => 1, 'title' => 'Founders']], $answer->segments);
    }

    public function test_custom_field_answer_from_array_multi_location_shape(): void
    {
        $answer = CustomFieldAnswer::fromArray([
            'member_id' => 42,
            'custom_field_id' => 3,
            'locations' => [['label' => 'San Francisco, CA', 'latitude' => 37.7749, 'longitude' => -122.4194]],
            'last_edited_at' => '2024-03-21T09:00:00+00:00',
        ]);

        $this->assertNull($answer->id);
        $this->assertSame(42, $answer->memberId);
        $this->assertNull($answer->userId);
        $this->assertCount(1, $answer->locations);
        $this->assertSame(37.7749, $answer->locations[0]['latitude']);
    }

    public function test_poll_from_array(): void
    {
        $poll = Poll::fromArray([
            'id' => 11,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'post_type' => 'poll',
            'poll_type' => 'multiple_choice_poll',
            'title' => 'Favourite colour?',
            'description' => 'Pick one',
            'creator' => ['id' => 1],
            'space' => ['id' => 2],
            'images' => ['https://cdn.mn.co/a.png'],
            'choices' => [['id' => 'a', 'text' => 'Blue']],
            'status' => 'published',
            'published_at' => '2024-01-16T08:00:00+00:00',
            'permalink' => 'https://example.mn.co/polls/11',
            'comments_enabled' => true,
            'last_activity_at' => '2024-03-21T09:00:00+00:00',
        ]);

        $this->assertSame(11, $poll->id);
        $this->assertSame('multiple_choice_poll', $poll->pollType);
        $this->assertSame('published', $poll->status);
        $this->assertTrue($poll->commentsEnabled);
        $this->assertSame([['id' => 'a', 'text' => 'Blue']], $poll->choices);
    }

    public function test_new_poll_data_emits_snake_case_and_omits_nulls(): void
    {
        $data = new NewPollData(
            spaceId: 5,
            title: 'Favourite colour?',
            pollType: PollType::MultipleChoice,
            choices: ['Blue', 'Red'],
            notify: true,
        );

        $this->assertSame([
            'space_id' => 5,
            'title' => 'Favourite colour?',
            'poll_type' => 'multiple_choice',
            'choices' => ['Blue', 'Red'],
            'notify' => true,
        ], $data->toArray());
    }

    public function test_new_poll_data_omits_missing_description(): void
    {
        $data = new NewPollData(
            spaceId: 5,
            title: 'Open question',
            pollType: 'question',
        );

        $this->assertSame([
            'space_id' => 5,
            'title' => 'Open question',
            'poll_type' => 'question',
        ], $data->toArray());
    }

    public function test_update_poll_data_omits_nulls(): void
    {
        $this->assertSame(['title' => 'New title'], (new UpdatePollData(title: 'New title'))->toArray());
        $this->assertSame([], (new UpdatePollData)->toArray());
    }

    public function test_new_tag_data_emits_snake_case_and_omits_nulls(): void
    {
        $this->assertSame([
            'title' => 'VIP Member',
            'color' => '#FF5733',
        ], (new NewTagData(title: 'VIP Member', color: '#FF5733'))->toArray());

        $this->assertSame([
            'title' => 'VIP Member',
            'description' => 'Premium tier members',
            'color' => '#FF5733',
        ], (new NewTagData(title: 'VIP Member', description: 'Premium tier members', color: '#FF5733'))->toArray());
    }

    public function test_update_tag_data_omits_nulls(): void
    {
        $this->assertSame(['description' => 'Updated'], (new UpdateTagData(description: 'Updated'))->toArray());
        $this->assertSame([], (new UpdateTagData)->toArray());
    }

    public function test_new_badge_data_emits_snake_case_and_omits_nulls(): void
    {
        $this->assertSame([
            'title' => 'Top Contributor',
            'avatar_id' => 555,
        ], (new NewBadgeData(title: 'Top Contributor', avatarId: 555))->toArray());

        $this->assertSame([
            'title' => 'Top Contributor',
            'description' => 'Awarded for contributions',
            'color' => '#FFD700',
            'avatar_id' => 555,
        ], (new NewBadgeData(
            title: 'Top Contributor',
            avatarId: 555,
            description: 'Awarded for contributions',
            color: '#FFD700',
        ))->toArray());
    }

    public function test_update_badge_data_omits_nulls(): void
    {
        $this->assertSame([
            'color' => '#000000',
            'avatar_id' => 999,
        ], (new UpdateBadgeData(color: '#000000', avatarId: 999))->toArray());

        $this->assertSame([], (new UpdateBadgeData)->toArray());
    }

    public function test_asset_from_array(): void
    {
        $asset = Asset::fromArray([
            'id' => 21,
            'url' => 'https://cdn.mn.co/assets/21.png',
            'type' => 'StaticAsset',
            'name' => 'logo.png',
        ]);

        $this->assertSame(21, $asset->id);
        $this->assertSame('StaticAsset', $asset->type);
        $this->assertSame('logo.png', $asset->name);
    }

    public function test_catalog_collections_are_typed(): void
    {
        $tag = Tag::fromArray(['id' => 1, 'created_at' => '2024-01-01T00:00:00+00:00', 'updated_at' => '2024-01-01T00:00:00+00:00', 'title' => 'T', 'custom_field_id' => 1]);

        $this->assertInstanceOf(TagCollection::class, (new TagCollection([$tag]))->ensure(Tag::class));
        $this->assertInstanceOf(BadgeCollection::class, (new BadgeCollection)->ensure(Badge::class));
        $this->assertInstanceOf(CustomFieldCollection::class, (new CustomFieldCollection)->ensure(CustomField::class));
        $this->assertInstanceOf(CustomFieldOptionCollection::class, (new CustomFieldOptionCollection)->ensure(CustomFieldOption::class));
        $this->assertInstanceOf(CustomFieldAnswerCollection::class, (new CustomFieldAnswerCollection)->ensure(CustomFieldAnswer::class));
        $this->assertInstanceOf(PollCollection::class, (new PollCollection)->ensure(Poll::class));
    }
}
