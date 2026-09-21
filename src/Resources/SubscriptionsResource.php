<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\SubscriptionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Subscription;
use MCKLtech\MightyNetworks\Enums\CancelTiming;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\DeleteSubscriptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\GetSubscriptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\ListSubscriptionsRequest;
use Saloon\Http\Response;

/**
 * The public payment-subscriptions API.
 */
final class SubscriptionsResource extends Resource
{
    /**
     * Fetch the first page of subscriptions, optionally filtered by status
     * (e.g. `canceled`) and/or member.
     */
    public function all(?string $status = null, ?int $memberId = null, int $perPage = 25): SubscriptionCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(
                new ListSubscriptionsRequest($this->networkId(), $status, $memberId, perPage: $perPage),
            ),
        );
    }

    /**
     * Lazily paginate through subscriptions, optionally filtered.
     */
    public function paginate(?string $status = null, ?int $memberId = null, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListSubscriptionsRequest($this->networkId(), $status, $memberId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every matching subscription, fetching pages lazily.
     *
     * @param  callable(Subscription): void  $callback
     */
    public function each(
        callable $callback,
        ?string $status = null,
        ?int $memberId = null,
        int $perPage = 25,
    ): void {
        foreach ($this->paginate($status, $memberId, $perPage)->items() as $item) {
            $callback($this->ensureSubscription($item));
        }
    }

    /**
     * Find a subscription by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Subscription
    {
        return $this->subscriptionFrom(
            $this->connector()->send(new GetSubscriptionRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Subscription
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Cancel a payment subscription.
     *
     * `$when` controls whether the cancellation is immediate
     * ({@see CancelTiming::Now}) or deferred ({@see CancelTiming::EndOfBillingCycle},
     * the API default).
     */
    public function cancel(int $id, CancelTiming|string|null $when = null): Subscription
    {
        return $this->subscriptionFrom(
            $this->connector()->send(new DeleteSubscriptionRequest($this->networkId(), $id, $when)),
        );
    }

    /**
     * Alias for {@see cancel()}, mirroring the DELETE verb used by the endpoint.
     */
    public function delete(int $id, CancelTiming|string|null $when = null): Subscription
    {
        return $this->cancel($id, $when);
    }

    private function subscriptionFrom(Response $response): Subscription
    {
        return $this->ensureSubscription($response->dto());
    }

    private function collectionFrom(Response $response): SubscriptionCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof SubscriptionCollection) {
            throw new MightyNetworksException('Expected a SubscriptionCollection from the subscriptions endpoint.');
        }

        return $dto;
    }

    private function ensureSubscription(mixed $value): Subscription
    {
        if (! $value instanceof Subscription) {
            throw new MightyNetworksException('Expected a Subscription from the subscriptions endpoint.');
        }

        return $value;
    }
}
