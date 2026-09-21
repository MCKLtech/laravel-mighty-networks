<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use Illuminate\Support\Collection;
use MCKLtech\MightyNetworks\Collections\CollectionGroupCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionGroup;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionOrderItem;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCollectionGroupData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCollectionGroupData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\CreateCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\DeleteCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\GetCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ListCollectionGroupsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ReorderCollectionSpacesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\ReplaceCollectionGroupRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Collections\UpdateCollectionGroupRequest;
use Saloon\Http\Response;

/**
 * The public collections API. A Mighty Networks "Collection" is a named
 * grouping of Spaces; the SDK calls it a {@see CollectionGroup} to avoid
 * colliding with `Illuminate\Support\Collection`.
 */
final class CollectionsResource extends Resource
{
    /**
     * Find a collection by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): CollectionGroup
    {
        return $this->collectionGroupFrom(
            $this->connector()->send(new GetCollectionGroupRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?CollectionGroup
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of collections.
     */
    public function all(int $perPage = 25): CollectionGroupCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListCollectionGroupsRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of collections.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListCollectionGroupsRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every collection, fetching pages lazily.
     *
     * @param  callable(CollectionGroup): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureCollectionGroup($item));
        }
    }

    /**
     * Create a new collection.
     */
    public function create(NewCollectionGroupData $data): CollectionGroup
    {
        return $this->collectionGroupFrom(
            $this->connector()->send(new CreateCollectionGroupRequest($this->networkId(), $data)),
        );
    }

    /**
     * Update an existing collection.
     */
    public function update(int $id, UpdateCollectionGroupData $data): CollectionGroup
    {
        return $this->collectionGroupFrom(
            $this->connector()->send(new UpdateCollectionGroupRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Fully replace a collection with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateCollectionGroupData $data): CollectionGroup
    {
        return $this->collectionGroupFrom(
            $this->connector()->send(new ReplaceCollectionGroupRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a collection.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteCollectionGroupRequest($this->networkId(), $id));
    }

    /**
     * Reorder the spaces within a collection.
     *
     * Pass the space IDs in the desired order; positions are assigned 1-based
     * from the array order. Returns the spaces with their new positions.
     *
     * @param  array<int, int>  $spaceIds
     * @return Collection<int, CollectionOrderItem>
     */
    public function reorder(int $id, array $spaceIds): Collection
    {
        $positions = [];

        foreach (array_values($spaceIds) as $index => $spaceId) {
            $positions[] = ['space_id' => $spaceId, 'position' => $index + 1];
        }

        $dto = $this->connector()
            ->send(new ReorderCollectionSpacesRequest($this->networkId(), $id, $positions))
            ->dto();

        if (! $dto instanceof Collection) {
            throw new MightyNetworksException('Expected a collection of CollectionOrderItem from the reorder endpoint.');
        }

        return $dto;
    }

    private function collectionGroupFrom(Response $response): CollectionGroup
    {
        return $this->ensureCollectionGroup($response->dto());
    }

    private function collectionFrom(Response $response): CollectionGroupCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CollectionGroupCollection) {
            throw new MightyNetworksException('Expected a CollectionGroupCollection from the collections endpoint.');
        }

        return $dto;
    }

    private function ensureCollectionGroup(mixed $value): CollectionGroup
    {
        if (! $value instanceof CollectionGroup) {
            throw new MightyNetworksException('Expected a CollectionGroup from the collections endpoint.');
        }

        return $value;
    }
}
