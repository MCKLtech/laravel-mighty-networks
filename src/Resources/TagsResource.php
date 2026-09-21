<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\TagCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\NewTagData;
use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateTagData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\AddTagToMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\CreateTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\DeleteTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\GetMemberTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\GetTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ListMemberTagsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ListTagsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\RemoveTagFromMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\ReplaceTagRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Tags\UpdateTagRequest;
use Saloon\Http\Response;

/**
 * The public tags API. Every method maps onto one Admin REST endpoint.
 */
final class TagsResource extends Resource
{
    /**
     * Find a tag by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new GetTagRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Tag
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of tags.
     */
    public function all(int $perPage = 25): TagCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListTagsRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of tags.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListTagsRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every tag, fetching pages lazily.
     *
     * @param  callable(Tag): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureTag($item));
        }
    }

    /**
     * Create a new tag.
     */
    public function create(NewTagData $data): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new CreateTagRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing tag (HTTP `PATCH`).
     */
    public function update(int $id, UpdateTagData $data): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new UpdateTagRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing tag with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateTagData $data): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new ReplaceTagRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a tag.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteTagRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of tags assigned to a member.
     */
    public function tagsForMember(int $memberId, int $perPage = 25): TagCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListMemberTagsRequest(
                networkId: $this->networkId(),
                memberId: $memberId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every tag assigned to a member.
     */
    public function paginateForMember(int $memberId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListMemberTagsRequest($this->networkId(), $memberId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Assign a tag to a member.
     */
    public function addToMember(int $memberId, int $tagId): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new AddTagToMemberRequest($this->networkId(), $memberId, $tagId)),
        );
    }

    /**
     * Fetch a single tag as assigned to a member.
     *
     * @throws NotFoundException
     */
    public function tagForMember(int $memberId, int $tagId): Tag
    {
        return $this->tagFrom(
            $this->connector()->send(new GetMemberTagRequest($this->networkId(), $memberId, $tagId)),
        );
    }

    /**
     * Remove a tag from a member.
     */
    public function removeFromMember(int $memberId, int $tagId): void
    {
        $this->connector()->send(new RemoveTagFromMemberRequest($this->networkId(), $memberId, $tagId));
    }

    private function tagFrom(Response $response): Tag
    {
        return $this->ensureTag($response->dto());
    }

    private function collectionFrom(Response $response): TagCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof TagCollection) {
            throw new MightyNetworksException('Expected a TagCollection from the tags endpoint.');
        }

        return $dto;
    }

    private function ensureTag(mixed $value): Tag
    {
        if (! $value instanceof Tag) {
            throw new MightyNetworksException('Expected a Tag from the tags endpoint.');
        }

        return $value;
    }
}
