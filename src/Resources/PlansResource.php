<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\InviteCollection;
use MCKLtech\MightyNetworks\Collections\MemberCollection;
use MCKLtech\MightyNetworks\Collections\PlanCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\DataTransferObjects\Plan;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\AddPlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\CreatePlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\DeletePlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\DeletePlanRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\GetPlanRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlanInvitesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlanMembersRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ListPlansRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\RemovePlanMemberRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\ResendPlanInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Plans\UpdatePlanInviteRequest;
use Saloon\Http\Response;

/**
 * The public plans API: plans themselves, their members, and their invites.
 */
final class PlansResource extends Resource
{
    /**
     * Fetch the first page of plans.
     */
    public function all(int $perPage = 25): PlanCollection
    {
        return $this->planCollectionFrom(
            $this->connector()->send(new ListPlansRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of plans.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPlansRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every plan, fetching pages lazily.
     *
     * @param  callable(Plan): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensurePlan($item));
        }
    }

    /**
     * Find a plan by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Plan
    {
        return $this->planFrom(
            $this->connector()->send(new GetPlanRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Plan
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Archive a plan. This cancels all associated subscriptions and revokes access.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeletePlanRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of members attached to a plan.
     */
    public function members(int $planId, int $perPage = 25): MemberCollection
    {
        return $this->memberCollectionFrom(
            $this->connector()->send(new ListPlanMembersRequest($this->networkId(), $planId, perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every member of a plan.
     */
    public function paginateMembers(int $planId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPlanMembersRequest($this->networkId(), $planId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every member of a plan, fetching pages lazily.
     *
     * @param  callable(Member): void  $callback
     */
    public function eachMember(int $planId, callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginateMembers($planId, $perPage)->items() as $item) {
            $callback($this->ensureMember($item));
        }
    }

    /**
     * Add a member directly to a free/nonpaid plan, granting access immediately.
     *
     * Returns the plan the member was added to.
     */
    public function addMember(int $planId, int $userId): Plan
    {
        return $this->planFrom(
            $this->connector()->send(new AddPlanMemberRequest($this->networkId(), $planId, $userId)),
        );
    }

    /**
     * Find a single member of a plan by their member (user) ID.
     *
     * @throws NotFoundException
     */
    public function member(int $planId, int $memberId): Member
    {
        return $this->memberFrom(
            $this->connector()->send(new GetPlanMemberRequest($this->networkId(), $planId, $memberId)),
        );
    }

    /**
     * Like {@see Member()} but returns null instead of throwing a 404.
     */
    public function memberOrNull(int $planId, int $memberId): ?Member
    {
        try {
            return $this->member($planId, $memberId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Remove a member from a plan by cancelling their subscription or purchase.
     */
    public function removeMember(int $planId, int $memberId): void
    {
        $this->connector()->send(new RemovePlanMemberRequest($this->networkId(), $planId, $memberId));
    }

    /**
     * Fetch the first page of invites for a plan.
     */
    public function invites(int $planId, int $perPage = 25): InviteCollection
    {
        return $this->inviteCollectionFrom(
            $this->connector()->send(new ListPlanInvitesRequest($this->networkId(), $planId, perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every invite for a plan.
     */
    public function paginateInvites(int $planId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPlanInvitesRequest($this->networkId(), $planId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every invite of a plan, fetching pages lazily.
     *
     * @param  callable(Invite): void  $callback
     */
    public function eachInvite(int $planId, callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginateInvites($planId, $perPage)->items() as $item) {
            $callback($this->ensureInvite($item));
        }
    }

    /**
     * Invite someone to a plan by email or user ID.
     *
     * Provide at least one of `$email` or `$userId`. `$message` and `$couponId`
     * are optional; `$couponId` pre-applies a promo code at checkout.
     */
    public function createInvite(
        int $planId,
        ?string $email = null,
        ?int $userId = null,
        ?string $message = null,
        ?int $couponId = null,
    ): Invite {
        return $this->inviteFrom(
            $this->connector()->send(
                new CreatePlanInviteRequest($this->networkId(), $planId, $email, $userId, $message, $couponId),
            ),
        );
    }

    /**
     * Find a single plan invite by its ID.
     *
     * @throws NotFoundException
     */
    public function invite(int $planId, int $inviteId): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(new GetPlanInviteRequest($this->networkId(), $planId, $inviteId)),
        );
    }

    /**
     * Like {@see Invite()} but returns null instead of throwing a 404.
     */
    public function inviteOrNull(int $planId, int $inviteId): ?Invite
    {
        try {
            return $this->invite($planId, $inviteId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Update an existing plan invite (partial update).
     */
    public function updateInvite(int $planId, int $inviteId, UpdateInviteData $data): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(
                new UpdatePlanInviteRequest($this->networkId(), $planId, $inviteId, $data),
            ),
        );
    }

    /**
     * Re-send an existing plan invite.
     */
    public function resendInvite(int $planId, int $inviteId): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(new ResendPlanInviteRequest($this->networkId(), $planId, $inviteId)),
        );
    }

    /**
     * Revoke an unaccepted plan invite.
     */
    public function deleteInvite(int $planId, int $inviteId): void
    {
        $this->connector()->send(new DeletePlanInviteRequest($this->networkId(), $planId, $inviteId));
    }

    private function planFrom(Response $response): Plan
    {
        return $this->ensurePlan($response->dto());
    }

    private function memberFrom(Response $response): Member
    {
        return $this->ensureMember($response->dto());
    }

    private function inviteFrom(Response $response): Invite
    {
        return $this->ensureInvite($response->dto());
    }

    private function planCollectionFrom(Response $response): PlanCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof PlanCollection) {
            throw new MightyNetworksException('Expected a PlanCollection from the plans endpoint.');
        }

        return $dto;
    }

    private function memberCollectionFrom(Response $response): MemberCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof MemberCollection) {
            throw new MightyNetworksException('Expected a MemberCollection from the plan members endpoint.');
        }

        return $dto;
    }

    private function inviteCollectionFrom(Response $response): InviteCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof InviteCollection) {
            throw new MightyNetworksException('Expected an InviteCollection from the plan invites endpoint.');
        }

        return $dto;
    }

    private function ensurePlan(mixed $value): Plan
    {
        if (! $value instanceof Plan) {
            throw new MightyNetworksException('Expected a Plan from the plans endpoint.');
        }

        return $value;
    }

    private function ensureMember(mixed $value): Member
    {
        if (! $value instanceof Member) {
            throw new MightyNetworksException('Expected a Member from the plan members endpoint.');
        }

        return $value;
    }

    private function ensureInvite(mixed $value): Invite
    {
        if (! $value instanceof Invite) {
            throw new MightyNetworksException('Expected an Invite from the plan invites endpoint.');
        }

        return $value;
    }
}
