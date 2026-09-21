<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\NewMemberData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateMemberData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Members\CreateMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\DeleteMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\FindMemberByEmailRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\GetMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ListMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\RemoveMemberFromNetworkRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\ReplaceMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\SendPasswordResetRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Members\UpdateMemberRequest;
use Saloon\Http\Response;

/**
 * The public members API. Every method maps onto one Admin REST endpoint.
 */
final class MembersResource extends Resource
{
    /**
     * Find a member by their numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new GetMemberRequest($this->networkId(), $id)),
        );
    }

    /**
     * Find a member by email address.
     *
     * @throws NotFoundException
     */
    public function findByEmail(string $email): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new FindMemberByEmailRequest($this->networkId(), $email)),
        );
    }

    /**
     * Like {@see findByEmail()} but returns null instead of throwing a 404.
     */
    public function findByEmailOrNull(string $email): ?Member
    {
        try {
            return $this->findByEmail($email);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of members.
     */
    public function all(int $perPage = 25): MemberCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListMembersRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of members.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListMembersRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every member, fetching pages lazily.
     *
     * @param  callable(Member): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureMember($item));
        }
    }

    /**
     * Create a new member.
     */
    public function create(NewMemberData $data): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new CreateMemberRequest($this->networkId(), $data)),
        );
    }

    /**
     * Update an existing member.
     */
    public function update(int $id, UpdateMemberData $data): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new UpdateMemberRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Fully replace a member's role and profile information (HTTP `PUT`).
     */
    public function replace(int $id, UpdateMemberData $data): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new ReplaceMemberRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a member.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteMemberRequest($this->networkId(), $id));
    }

    /**
     * Remove a member from the Network without deleting their account.
     */
    public function removeFromNetwork(int $id): void
    {
        $this->connector()->send(new RemoveMemberFromNetworkRequest($this->networkId(), $id));
    }

    /**
     * Trigger a password-reset email for a member.
     */
    public function sendPasswordReset(int $id): void
    {
        $this->connector()->send(new SendPasswordResetRequest($this->networkId(), $id));
    }

    private function memberFrom(Response $response): Member
    {
        return $this->ensureMember($response->dto());
    }

    private function collectionFrom(Response $response): MemberCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof MemberCollection) {
            throw new MightyNetworksException('Expected a MemberCollection from the members endpoint.');
        }

        return $dto;
    }

    private function ensureMember(mixed $value): Member
    {
        if (! $value instanceof Member) {
            throw new MightyNetworksException('Expected a Member from the members endpoint.');
        }

        return $value;
    }
}
