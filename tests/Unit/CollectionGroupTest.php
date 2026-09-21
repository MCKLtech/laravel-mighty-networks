<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\DataTransferObjects\CollectionGroup;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionOrderItem;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCollectionGroupData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCollectionGroupData;
use MCKLtech\MightyNetworks\Tests\TestCase;

final class CollectionGroupTest extends TestCase
{
    public function test_collection_group_from_array_maps_all_fields(): void
    {
        $collection = CollectionGroup::fromArray([
            'id' => 5,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Featured Courses',
            'description' => 'Our most popular courses',
            'visible_to_members' => true,
            'position' => 2,
            'explorable' => false,
        ]);

        $this->assertSame(5, $collection->id);
        $this->assertSame('Featured Courses', $collection->name);
        $this->assertSame('Our most popular courses', $collection->description);
        $this->assertTrue($collection->visibleToMembers);
        $this->assertSame(2, $collection->position);
        $this->assertFalse($collection->explorable);
        $this->assertSame('2024-03-20T14:22:00+00:00', $collection->updatedAt->toIso8601String());
    }

    public function test_collection_group_from_array_handles_a_missing_description(): void
    {
        $collection = CollectionGroup::fromArray([
            'id' => 5,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Featured Courses',
            'visible_to_members' => true,
            'position' => 1,
            'explorable' => true,
        ]);

        $this->assertNull($collection->description);
    }

    public function test_new_collection_group_data_serializes_to_snake_case_and_omits_nulls(): void
    {
        $this->assertSame(
            ['name' => 'Featured', 'visible_to_members' => true],
            (new NewCollectionGroupData(name: 'Featured', visibleToMembers: true))->toArray(),
        );
    }

    public function test_update_collection_group_data_omits_nulls(): void
    {
        $this->assertSame(
            ['description' => 'Updated'],
            (new UpdateCollectionGroupData(description: 'Updated'))->toArray(),
        );

        $this->assertSame([], (new UpdateCollectionGroupData)->toArray());
    }

    public function test_collection_order_item_from_array(): void
    {
        $item = CollectionOrderItem::fromArray(['id' => 12, 'name' => 'Intro', 'position' => 1]);

        $this->assertSame(12, $item->id);
        $this->assertSame('Intro', $item->name);
        $this->assertSame(1, $item->position);
    }
}
