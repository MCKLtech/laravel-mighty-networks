<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Collections\EventCollection;
use MCKLtech\MightyNetworks\Collections\RsvpCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Event;
use MCKLtech\MightyNetworks\DataTransferObjects\NewEventData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewRsvpData;
use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateEventData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateRsvpData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Events\CreateEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\DeleteEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\GetEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\ListEventsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\ReplaceEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\UpdateEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\CreateRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\DeleteRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\GetRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\ListRsvpsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\ReplaceRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\UpdateRsvpRequest;
use Saloon\Http\Response;

/**
 * The public events API, including the RSVPs nested beneath an event.
 *
 * Every method maps onto one Admin REST endpoint.
 */
final class EventsResource extends Resource
{
    /**
     * Fetch the first page of events.
     */
    public function all(int $perPage = 25): EventCollection
    {
        return $this->eventCollectionFrom(
            $this->connector()->send(new ListEventsRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of events.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListEventsRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every event, fetching pages lazily.
     *
     * @param  callable(Event): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureEvent($item));
        }
    }

    /**
     * Find an event by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Event
    {
        return $this->eventFrom(
            $this->connector()->send(new GetEventRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Event
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Create a new event.
     */
    public function create(NewEventData $data): Event
    {
        return $this->eventFrom(
            $this->connector()->send(new CreateEventRequest($this->networkId(), $data)),
        );
    }

    /**
     * Partially update an existing event (HTTP `PATCH`).
     */
    public function update(int $id, UpdateEventData $data): Event
    {
        return $this->eventFrom(
            $this->connector()->send(new UpdateEventRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Replace an existing event with the supplied representation (HTTP `PUT`).
     */
    public function replace(int $id, UpdateEventData $data): Event
    {
        return $this->eventFrom(
            $this->connector()->send(new ReplaceEventRequest($this->networkId(), $id, $data)),
        );
    }

    /**
     * Permanently delete an event.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeleteEventRequest($this->networkId(), $id));
    }

    /**
     * Fetch the first page of RSVPs for an event.
     *
     * Pass `$instanceAt` to narrow the list to a specific instance of a
     * recurring event.
     */
    public function rsvps(
        int $eventId,
        CarbonImmutable|string|null $instanceAt = null,
        int $perPage = 25,
    ): RsvpCollection {
        return $this->rsvpCollectionFrom(
            $this->connector()->send(
                new ListRsvpsRequest($this->networkId(), $eventId, $instanceAt, perPage: $perPage),
            ),
        );
    }

    /**
     * Lazily paginate through every RSVP for an event.
     */
    public function paginateRsvps(
        int $eventId,
        CarbonImmutable|string|null $instanceAt = null,
        int $perPage = 25,
    ): AdminPagedPaginator {
        return $this->connector()
            ->paginate(new ListRsvpsRequest($this->networkId(), $eventId, $instanceAt))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every RSVP of an event, fetching pages lazily.
     *
     * @param  callable(Rsvp): void  $callback
     */
    public function eachRsvp(
        int $eventId,
        callable $callback,
        CarbonImmutable|string|null $instanceAt = null,
        int $perPage = 25,
    ): void {
        foreach ($this->paginateRsvps($eventId, $instanceAt, $perPage)->items() as $item) {
            $callback($this->ensureRsvp($item));
        }
    }

    /**
     * Find a single RSVP by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function rsvp(int $eventId, int $rsvpId): Rsvp
    {
        return $this->rsvpFrom(
            $this->connector()->send(new GetRsvpRequest($this->networkId(), $eventId, $rsvpId)),
        );
    }

    /**
     * Like {@see Rsvp()} but returns null instead of throwing a 404.
     */
    public function rsvpOrNull(int $eventId, int $rsvpId): ?Rsvp
    {
        try {
            return $this->rsvp($eventId, $rsvpId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Create or update an RSVP for an event.
     */
    public function createRsvp(int $eventId, NewRsvpData $data): Rsvp
    {
        return $this->rsvpFrom(
            $this->connector()->send(new CreateRsvpRequest($this->networkId(), $eventId, $data)),
        );
    }

    /**
     * Partially update an existing RSVP (HTTP `PATCH`).
     */
    public function updateRsvp(int $eventId, int $rsvpId, UpdateRsvpData $data): Rsvp
    {
        return $this->rsvpFrom(
            $this->connector()->send(new UpdateRsvpRequest($this->networkId(), $eventId, $rsvpId, $data)),
        );
    }

    /**
     * Replace an existing RSVP with the supplied representation (HTTP `PUT`).
     */
    public function replaceRsvp(int $eventId, int $rsvpId, UpdateRsvpData $data): Rsvp
    {
        return $this->rsvpFrom(
            $this->connector()->send(new ReplaceRsvpRequest($this->networkId(), $eventId, $rsvpId, $data)),
        );
    }

    /**
     * Delete an RSVP.
     */
    public function deleteRsvp(int $eventId, int $rsvpId): void
    {
        $this->connector()->send(new DeleteRsvpRequest($this->networkId(), $eventId, $rsvpId));
    }

    private function eventFrom(Response $response): Event
    {
        return $this->ensureEvent($response->dto());
    }

    private function eventCollectionFrom(Response $response): EventCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof EventCollection) {
            throw new MightyNetworksException('Expected an EventCollection from the events endpoint.');
        }

        return $dto;
    }

    private function rsvpFrom(Response $response): Rsvp
    {
        return $this->ensureRsvp($response->dto());
    }

    private function rsvpCollectionFrom(Response $response): RsvpCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof RsvpCollection) {
            throw new MightyNetworksException('Expected an RsvpCollection from the rsvps endpoint.');
        }

        return $dto;
    }

    private function ensureEvent(mixed $value): Event
    {
        if (! $value instanceof Event) {
            throw new MightyNetworksException('Expected an Event from the events endpoint.');
        }

        return $value;
    }

    private function ensureRsvp(mixed $value): Rsvp
    {
        if (! $value instanceof Rsvp) {
            throw new MightyNetworksException('Expected an Rsvp from the rsvps endpoint.');
        }

        return $value;
    }
}
