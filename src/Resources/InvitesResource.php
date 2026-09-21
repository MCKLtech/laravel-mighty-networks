<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\InviteCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\NewInviteData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\CreateInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\DeleteInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\ListInvitesRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\ReplaceInviteRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Invites\UpdateInviteRequest;
use Saloon\Http\Response;

/**
 * The public Network invites API.
 *
 * Note: the Admin REST API does not expose a `GET invites/{id}/` endpoint, so
 * there is no `findById()` here. To look up an invite by ID, use the plan-scoped
 * {@see PlansResource::invite()}.
 */
final class InvitesResource extends Resource
{
    /**
     * Fetch the first page of Network invites, optionally filtered by recipient email.
     */
    public function all(?string $email = null, int $perPage = 25): InviteCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListInvitesRequest($this->networkId(), $email, perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through Network invites, optionally filtered by email.
     */
    public function paginate(?string $email = null, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListInvitesRequest($this->networkId(), $email))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every matching invite, fetching pages lazily.
     *
     * @param  callable(Invite): void  $callback
     */
    public function each(callable $callback, ?string $email = null, int $perPage = 25): void
    {
        foreach ($this->paginate($email, $perPage)->items() as $item) {
            $callback($this->ensureInvite($item));
        }
    }

    /**
     * Create an invite and email the intended recipient.
     */
    public function create(NewInviteData $data): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(new CreateInviteRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing invite (PATCH); omitted fields are untouched.
     */
    public function update(int $id, UpdateInviteData $data): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(new UpdateInviteRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing invite (PUT); `recipient_email` is required.
     */
    public function replace(int $id, NewInviteData $data): Invite
    {
        return $this->inviteFrom(
            $this->connector()->send(new ReplaceInviteRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Delete an unaccepted invite.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteInviteRequest($this->networkId(), $id));
    }

    private function inviteFrom(Response $response): Invite
    {
        return $this->ensureInvite($response->dto());
    }

    private function collectionFrom(Response $response): InviteCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof InviteCollection) {
            throw new MightyNetworksException('Expected an InviteCollection from the invites endpoint.');
        }

        return $dto;
    }

    private function ensureInvite(mixed $value): Invite
    {
        if (! $value instanceof Invite) {
            throw new MightyNetworksException('Expected an Invite from the invites endpoint.');
        }

        return $value;
    }
}
