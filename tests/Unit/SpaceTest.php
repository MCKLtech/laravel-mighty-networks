<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\NewSpaceData;
use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateSpaceData;
use MCKLtech\MightyNetworks\Tests\TestCase;

final class SpaceTest extends TestCase
{
    public function test_space_from_array_maps_all_fields(): void
    {
        $space = Space::fromArray([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Announcements',
            'collection_id' => 3,
        ]);

        $this->assertSame(7, $space->id);
        $this->assertSame('Announcements', $space->name);
        $this->assertSame(3, $space->collectionId);
        $this->assertInstanceOf(CarbonImmutable::class, $space->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $space->createdAt->toIso8601String());
    }

    public function test_space_from_array_handles_a_missing_collection_id(): void
    {
        $space = Space::fromArray([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Announcements',
        ]);

        $this->assertNull($space->collectionId);
    }

    public function test_new_space_data_serializes_to_snake_case(): void
    {
        $this->assertSame(
            ['name' => 'Announcements'],
            (new NewSpaceData(name: 'Announcements'))->toArray(),
        );
    }

    public function test_update_space_data_omits_nulls(): void
    {
        $this->assertSame(
            ['collection_id' => 9],
            (new UpdateSpaceData(collectionId: 9))->toArray(),
        );

        $this->assertSame([], (new UpdateSpaceData)->toArray());
    }
}
