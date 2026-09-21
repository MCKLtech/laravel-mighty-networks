<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\PurchaseCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\DeletePurchaseRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\GetPurchaseRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Purchases\ListPurchasesRequest;
use Saloon\Http\Response;

/**
 * The public purchases API. A purchase records a member's one-time access to a
 * plan; revoking it removes that access.
 */
final class PurchasesResource extends Resource
{
    /**
     * Fetch the first page of purchases, optionally filtered by plan and/or member.
     */
    public function all(?int $planId = null, ?int $memberId = null, int $perPage = 25): PurchaseCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(
                new ListPurchasesRequest($this->networkId(), $planId, $memberId, perPage: $perPage),
            ),
        );
    }

    /**
     * Lazily paginate through purchases, optionally filtered.
     */
    public function paginate(?int $planId = null, ?int $memberId = null, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPurchasesRequest($this->networkId(), $planId, $memberId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every matching purchase, fetching pages lazily.
     *
     * @param  callable(Purchase): void  $callback
     */
    public function each(
        callable $callback,
        ?int $planId = null,
        ?int $memberId = null,
        int $perPage = 25,
    ): void {
        foreach ($this->paginate($planId, $memberId, $perPage)->items() as $item) {
            $callback($this->ensurePurchase($item));
        }
    }

    /**
     * Find a purchase by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Purchase
    {
        return $this->purchaseFrom(
            $this->connector()->send(new GetPurchaseRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Purchase
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Remove a member from a plan by revoking their purchase access.
     *
     * Note: members on Apple In-App Purchases cannot be removed. Pass
     * `$immediate = true` to revoke without a grace period.
     */
    public function revoke(int $id, ?bool $immediate = null): Purchase
    {
        return $this->purchaseFrom(
            $this->connector()->send(new DeletePurchaseRequest($this->networkId(), $id, $immediate)),
        );
    }

    /**
     * Alias for {@see revoke()}, mirroring the DELETE verb used by the endpoint.
     */
    public function delete(int $id, ?bool $immediate = null): Purchase
    {
        return $this->revoke($id, $immediate);
    }

    private function purchaseFrom(Response $response): Purchase
    {
        return $this->ensurePurchase($response->dto());
    }

    private function collectionFrom(Response $response): PurchaseCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof PurchaseCollection) {
            throw new MightyNetworksException('Expected a PurchaseCollection from the purchases endpoint.');
        }

        return $dto;
    }

    private function ensurePurchase(mixed $value): Purchase
    {
        if (! $value instanceof Purchase) {
            throw new MightyNetworksException('Expected a Purchase from the purchases endpoint.');
        }

        return $value;
    }
}
