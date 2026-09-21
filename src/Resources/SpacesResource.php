<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\CourseworkCollection;
use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\Collections\SpaceCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewSpaceData;
use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateSpaceData;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\AddSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\BanSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\CreateCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\CreateSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\DeleteCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\DeleteSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\GetSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListCourseworksRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListSpaceMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ListSpacesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\RemoveSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\ReplaceSpaceRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateCourseworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateSpaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Spaces\UpdateSpaceRequest;
use Saloon\Http\Response;

/**
 * The public spaces API, including space memberships and course coursework.
 * Every method maps onto one Admin REST endpoint.
 */
final class SpacesResource extends Resource
{
    /**
     * Find a space by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Space
    {
        return $this->spaceFrom(
            $this->connector()->send(new GetSpaceRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Space
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of spaces.
     */
    public function all(int $perPage = 25): SpaceCollection
    {
        return $this->spaceCollectionFrom(
            $this->connector()->send(new ListSpacesRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of spaces.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListSpacesRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every space, fetching pages lazily.
     *
     * @param  callable(Space): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureSpace($item));
        }
    }

    /**
     * Create a new space.
     */
    public function create(NewSpaceData $data): Space
    {
        return $this->spaceFrom(
            $this->connector()->send(new CreateSpaceRequest($this->networkId(), $data)),
        );
    }

    /**
     * Update an existing space.
     */
    public function update(int $id, UpdateSpaceData $data): Space
    {
        return $this->spaceFrom(
            $this->connector()->send(new UpdateSpaceRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Fully replace a space with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateSpaceData $data): Space
    {
        return $this->spaceFrom(
            $this->connector()->send(new ReplaceSpaceRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a space.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteSpaceRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of members of a space.
     */
    public function members(int $spaceId, int $perPage = 25): MemberCollection
    {
        return $this->memberCollectionFrom(
            $this->connector()->send(new ListSpaceMembersRequest($this->networkId(), $spaceId, perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every member of a space.
     */
    public function paginateMembers(int $spaceId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListSpaceMembersRequest($this->networkId(), $spaceId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every member of a space, fetching pages lazily.
     *
     * @param  callable(Member): void  $callback
     */
    public function eachMember(int $spaceId, callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginateMembers($spaceId, $perPage)->items() as $item) {
            $callback($this->ensureMember($item));
        }
    }

    /**
     * Find a single member of a space by user ID.
     *
     * @throws NotFoundException
     */
    public function findMember(int $spaceId, int $userId): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new GetSpaceMemberRequest($this->networkId(), $spaceId, $userId)),
        );
    }

    /**
     * Like {@see findMember()} but returns null instead of throwing a 404.
     */
    public function findMemberOrNull(int $spaceId, int $userId): ?Member
    {
        try {
            return $this->findMember($spaceId, $userId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Add an existing Network user to a space.
     */
    public function addMember(int $spaceId, int $userId): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new AddSpaceMemberRequest($this->networkId(), $spaceId, $userId)),
        );
    }

    /**
     * Update a member's role (and optionally profile fields) within a space.
     */
    public function updateMember(int $spaceId, int $userId, UpdateMemberData $data): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new UpdateSpaceMemberRequest($this->networkId(), $spaceId, $userId, $data)),
        );
    }

    /**
     * Fully replace a member's role (and optionally profile fields) within a
     * space (HTTP `PUT`).
     */
    public function replaceMember(int $spaceId, int $userId, UpdateMemberData $data): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new ReplaceSpaceMemberRequest($this->networkId(), $spaceId, $userId, $data)),
        );
    }

    /**
     * Remove a member from a space. Their Network account is untouched.
     */
    public function removeMember(int $spaceId, int $userId): void
    {
        $this->connector()->send(new RemoveSpaceMemberRequest($this->networkId(), $spaceId, $userId));
    }

    /**
     * Ban a user from the entire Network.
     */
    public function banMember(int $spaceId, int $userId, ?string $reason = null): void
    {
        $this->connector()->send(new BanSpaceMemberRequest($this->networkId(), $spaceId, $userId, $reason));
    }

    /**
     * Fetch the first page of coursework items in a course space, with
     * optional filters for type, status, and parent item.
     */
    public function courseworks(
        int $spaceId,
        CourseworkType|string|null $type = null,
        CourseworkStatus|string|null $status = null,
        ?int $parentId = null,
        int $perPage = 25,
    ): CourseworkCollection {
        return $this->courseworkCollectionFrom(
            $this->connector()->send(new ListCourseworksRequest(
                $this->networkId(),
                $spaceId,
                type: $type,
                status: $status,
                parentId: $parentId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every coursework item in a course space.
     */
    public function paginateCourseworks(
        int $spaceId,
        CourseworkType|string|null $type = null,
        CourseworkStatus|string|null $status = null,
        ?int $parentId = null,
        int $perPage = 25,
    ): AdminPagedPaginator {
        return $this->connector()
            ->paginate(new ListCourseworksRequest(
                $this->networkId(),
                $spaceId,
                type: $type,
                status: $status,
                parentId: $parentId,
            ))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every coursework item in a course space, fetching
     * pages lazily.
     *
     * @param  callable(Coursework): void  $callback
     */
    public function eachCoursework(
        int $spaceId,
        callable $callback,
        CourseworkType|string|null $type = null,
        CourseworkStatus|string|null $status = null,
        ?int $parentId = null,
        int $perPage = 25,
    ): void {
        foreach ($this->paginateCourseworks($spaceId, $type, $status, $parentId, $perPage)->items() as $item) {
            $callback($this->ensureCoursework($item));
        }
    }

    /**
     * Find a single coursework item by ID.
     *
     * @throws NotFoundException
     */
    public function findCoursework(int $spaceId, int $courseworkId): Coursework
    {
        return $this->courseworkFrom(
            $this->connector()->send(new GetCourseworkRequest($this->networkId(), $spaceId, $courseworkId)),
        );
    }

    /**
     * Like {@see findCoursework()} but returns null instead of throwing a 404.
     */
    public function findCourseworkOrNull(int $spaceId, int $courseworkId): ?Coursework
    {
        try {
            return $this->findCoursework($spaceId, $courseworkId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Create a new coursework item (lesson, quiz, or section) in a course space.
     */
    public function createCoursework(int $spaceId, NewCourseworkData $data): Coursework
    {
        return $this->courseworkFrom(
            $this->connector()->send(new CreateCourseworkRequest($this->networkId(), $spaceId, $data)),
        );
    }

    /**
     * Update an existing coursework item.
     */
    public function updateCoursework(int $spaceId, int $courseworkId, UpdateCourseworkData $data): Coursework
    {
        return $this->courseworkFrom(
            $this->connector()->send(new UpdateCourseworkRequest($this->networkId(), $spaceId, $courseworkId, $data)),
        );
    }

    /**
     * Fully replace a coursework item with the supplied representation (HTTP `PUT`).
     */
    public function replaceCoursework(int $spaceId, int $courseworkId, UpdateCourseworkData $data): Coursework
    {
        return $this->courseworkFrom(
            $this->connector()->send(
                new ReplaceCourseworkRequest($this->networkId(), $spaceId, $courseworkId, $data),
            ),
        );
    }

    /**
     * Permanently delete a coursework item.
     */
    public function deleteCoursework(int $spaceId, int $courseworkId): void
    {
        $this->connector()->send(new DeleteCourseworkRequest($this->networkId(), $spaceId, $courseworkId));
    }

    private function spaceFrom(Response $response): Space
    {
        return $this->ensureSpace($response->dto());
    }

    private function spaceCollectionFrom(Response $response): SpaceCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof SpaceCollection) {
            throw new MightyNetworksException('Expected a SpaceCollection from the spaces endpoint.');
        }

        return $dto;
    }

    private function ensureSpace(mixed $value): Space
    {
        if (! $value instanceof Space) {
            throw new MightyNetworksException('Expected a Space from the spaces endpoint.');
        }

        return $value;
    }

    private function memberFrom(Response $response): Member
    {
        return $this->ensureMember($response->dto());
    }

    private function memberCollectionFrom(Response $response): MemberCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof MemberCollection) {
            throw new MightyNetworksException('Expected a MemberCollection from the space members endpoint.');
        }

        return $dto;
    }

    private function ensureMember(mixed $value): Member
    {
        if (! $value instanceof Member) {
            throw new MightyNetworksException('Expected a Member from the space members endpoint.');
        }

        return $value;
    }

    private function courseworkFrom(Response $response): Coursework
    {
        return $this->ensureCoursework($response->dto());
    }

    private function courseworkCollectionFrom(Response $response): CourseworkCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CourseworkCollection) {
            throw new MightyNetworksException('Expected a CourseworkCollection from the courseworks endpoint.');
        }

        return $dto;
    }

    private function ensureCoursework(mixed $value): Coursework
    {
        if (! $value instanceof Coursework) {
            throw new MightyNetworksException('Expected a Coursework from the courseworks endpoint.');
        }

        return $value;
    }
}
