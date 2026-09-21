<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Illuminate\Support\Collection;
use MCKLtech\MightyNetworks\Collections\CollectionGroupCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionGroup;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionOrderItem;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCollectionGroupData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCollectionGroupData;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\CreateCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\DeleteCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\GetCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ListCollectionGroupsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ReorderCollectionSpacesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\UpdateCollectionGroupRequest;
use MCKLtech\MightyNetworks\Resources\CollectionsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class CollectionsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function collectionPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 5,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'name' => 'Featured Courses',
            'description' => 'Our most popular courses',
            'visible_to_members' => true,
            'position' => 1,
            'explorable' => false,
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_collection_group_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->collectionPayload(), 200)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $collection = $resource->findById(5);

        $this->assertInstanceOf(CollectionGroup::class, $collection);
        $this->assertSame(5, $collection->id);
        $this->assertSame('Featured Courses', $collection->name);
        $this->assertTrue($collection->visibleToMembers);
        $this->assertSame(1, $collection->position);
        $this->assertFalse($collection->explorable);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCollectionGroupRequest
                && $request->resolveEndpoint() === 'networks/12345/collections/5/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Collection not found'], 404)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(999));
    }

    public function test_all_returns_a_typed_collection_for_the_first_page(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->collectionPayload(['id' => 1]), $this->collectionPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $collections = $resource->all(perPage: 50);

        $this->assertInstanceOf(CollectionGroupCollection::class, $collections);
        $this->assertCount(2, $collections);
        $this->assertSame(
            [1, 2],
            $collections->map(static fn (CollectionGroup $collection): int => $collection->id)->all(),
        );

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCollectionGroupsRequest
                && $request->resolveEndpoint() === 'networks/12345/collections'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_posts_the_dto_body_and_returns_a_collection_group(): void
    {
        $mock = new MockClient([MockResponse::make($this->collectionPayload(['id' => 99]), 201)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $collection = $resource->create(new NewCollectionGroupData(
            name: 'Featured',
            description: 'Top picks',
            visibleToMembers: true,
        ));

        $this->assertSame(99, $collection->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCollectionGroupRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/collections'
                && $request->body()->all() === [
                    'name' => 'Featured',
                    'description' => 'Top picks',
                    'visible_to_members' => true,
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->collectionPayload(), 200)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $resource->update(5, new UpdateCollectionGroupData(name: 'Renamed', visibleToMembers: false));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateCollectionGroupRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/collections/5/'
                && $request->body()->all() === ['name' => 'Renamed', 'visible_to_members' => false];
        });
    }

    public function test_delete_sends_a_delete_to_the_collection_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $resource->delete(5);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCollectionGroupRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/collections/5/';
        });
    }

    public function test_reorder_puts_space_positions_and_returns_order_items(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [
                    ['id' => 12, 'name' => 'Intro', 'position' => 1],
                    ['id' => 9, 'name' => 'Advanced', 'position' => 2],
                ],
            ], 200),
        ]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $items = $resource->reorder(5, [12, 9]);

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(CollectionOrderItem::class, $items);

        $first = $items->first();

        $this->assertInstanceOf(CollectionOrderItem::class, $first);
        $this->assertSame(12, $first->id);
        $this->assertSame(1, $first->position);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReorderCollectionSpacesRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/collections/5/order'
                && $request->body()->all() === [
                    'spaces' => [
                        ['space_id' => 12, 'position' => 1],
                        ['space_id' => 9, 'position' => 2],
                    ],
                ];
        });
    }

    public function test_it_paginates_collections_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->collectionPayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->collectionPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 50)->items() as $collection) {
            $this->assertInstanceOf(CollectionGroup::class, $collection);
            $ids[] = $collection->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_collection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->collectionPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 1, 'total_count' => 1, 'per_page' => 25],
            ], 200),
        ]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (CollectionGroup $collection) use (&$ids): void {
            $ids[] = $collection->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Collection not found'], 404)]);

        $resource = new CollectionsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(5);
    }
}
