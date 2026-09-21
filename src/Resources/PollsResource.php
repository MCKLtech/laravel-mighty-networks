<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\PollCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPollData;
use MCKLtech\MightyNetworks\DataTransferObjects\Poll;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePollData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\CreatePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\DeletePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\GetPollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\ListPollsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\ReplacePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\UpdatePollRequest;
use Saloon\Http\Response;

/**
 * The public polls API. Every method maps onto one Admin REST endpoint.
 */
final class PollsResource extends Resource
{
    /**
     * Find a poll by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Poll
    {
        return $this->pollFrom(
            $this->connector()->send(new GetPollRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Poll
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Fetch the first page of polls, optionally scoped to a space.
     */
    public function all(?int $spaceId = null, int $perPage = 25): PollCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListPollsRequest(
                networkId: $this->networkId(),
                spaceId: $spaceId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every poll, optionally scoped to a space.
     */
    public function paginate(?int $spaceId = null, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPollsRequest($this->networkId(), $spaceId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every poll, fetching pages lazily.
     *
     * @param  callable(Poll): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate(perPage: $perPage)->items() as $item) {
            $callback($this->ensurePoll($item));
        }
    }

    /**
     * Create a new poll or question.
     */
    public function create(NewPollData $data): Poll
    {
        return $this->pollFrom(
            $this->connector()->send(new CreatePollRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing poll or question (HTTP `PATCH`).
     */
    public function update(int $id, UpdatePollData $data): Poll
    {
        return $this->pollFrom(
            $this->connector()->send(new UpdatePollRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing poll with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdatePollData $data): Poll
    {
        return $this->pollFrom(
            $this->connector()->send(new ReplacePollRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete a poll or question.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeletePollRequest($this->networkId(), $id));
    }

    private function pollFrom(Response $response): Poll
    {
        return $this->ensurePoll($response->dto());
    }

    private function collectionFrom(Response $response): PollCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof PollCollection) {
            throw new MightyNetworksException('Expected a PollCollection from the polls endpoint.');
        }

        return $dto;
    }

    private function ensurePoll(mixed $value): Poll
    {
        if (! $value instanceof Poll) {
            throw new MightyNetworksException('Expected a Poll from the polls endpoint.');
        }

        return $value;
    }
}
