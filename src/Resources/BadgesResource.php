<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\BadgeCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\DataTransferObjects\NewBadgeData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateBadgeData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\AddBadgeToMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\CreateBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\DeleteBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\GetBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\GetMemberBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ListBadgesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ListMemberBadgesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\RemoveBadgeFromMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\ReplaceBadgeRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Badges\UpdateBadgeRequest;
use Saloon\Http\Response;

/**
 * The public badges API. Every method maps onto one Admin REST endpoint.
 */
final class BadgesResource extends Resource
{
    /**
     * Find a badge by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new GetBadgeRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Badge
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of badges.
     */
    public function all(int $perPage = 25): BadgeCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListBadgesRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of badges.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListBadgesRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every badge, fetching pages lazily.
     *
     * @param  callable(Badge): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureBadge($item));
        }
    }

    /**
     * Create a new badge.
     *
     * The badge image must already be uploaded as an asset; pass its ID as
     * `$avatarId` (see {@see AssetsResource}).
     */
    public function create(NewBadgeData $data): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new CreateBadgeRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing badge (HTTP `PATCH`).
     */
    public function update(int $id, UpdateBadgeData $data): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new UpdateBadgeRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing badge with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateBadgeData $data): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new ReplaceBadgeRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a badge.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteBadgeRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of badges assigned to a member.
     */
    public function badgesForMember(int $memberId, int $perPage = 25): BadgeCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListMemberBadgesRequest(
                networkId: $this->networkId(),
                memberId: $memberId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every badge assigned to a member.
     */
    public function paginateForMember(int $memberId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListMemberBadgesRequest($this->networkId(), $memberId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Assign a badge to a member.
     */
    public function addToMember(int $memberId, int $badgeId): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new AddBadgeToMemberRequest($this->networkId(), $memberId, $badgeId)),
        );
    }

    /**
     * Fetch a single badge as assigned to a member.
     *
     * @throws NotFoundException
     */
    public function badgeForMember(int $memberId, int $badgeId): Badge
    {
        return $this->badgeFrom(
            $this->connector()->send(new GetMemberBadgeRequest($this->networkId(), $memberId, $badgeId)),
        );
    }

    /**
     * Remove a badge from a member.
     */
    public function removeFromMember(int $memberId, int $badgeId): void
    {
        $this->connector()->send(new RemoveBadgeFromMemberRequest($this->networkId(), $memberId, $badgeId));
    }

    private function badgeFrom(Response $response): Badge
    {
        return $this->ensureBadge($response->dto());
    }

    private function collectionFrom(Response $response): BadgeCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof BadgeCollection) {
            throw new MightyNetworksException('Expected a BadgeCollection from the badges endpoint.');
        }

        return $dto;
    }

    private function ensureBadge(mixed $value): Badge
    {
        if (! $value instanceof Badge) {
            throw new MightyNetworksException('Expected a Badge from the badges endpoint.');
        }

        return $value;
    }
}
