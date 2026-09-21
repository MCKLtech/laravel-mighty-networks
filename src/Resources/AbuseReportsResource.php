<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\AbuseReportCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\AbuseReport;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\AbuseReports\ListAbuseReportsRequest;
use Saloon\Http\Response;

/**
 * The public abuse reports API. Every method maps onto one Admin REST endpoint.
 */
final class AbuseReportsResource extends Resource
{
    /**
     * Fetch the first page of abuse reports for the Network.
     */
    public function all(int $perPage = 25): AbuseReportCollection
    {
        return $this->collectionFrom(
            $this->connector()->send(new ListAbuseReportsRequest($this->networkId(), perPage: $perPage)),
        );
    }

    /**
     * Lazily paginate through every page of abuse reports.
     */
    public function paginate(int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListAbuseReportsRequest($this->networkId()))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every abuse report, fetching pages lazily.
     *
     * @param  callable(AbuseReport): void  $callback
     */
    public function each(callable $callback, int $perPage = 25): void
    {
        foreach ($this->paginate($perPage)->items() as $item) {
            $callback($this->ensureAbuseReport($item));
        }
    }

    private function collectionFrom(Response $response): AbuseReportCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof AbuseReportCollection) {
            throw new MightyNetworksException('Expected an AbuseReportCollection from the abuse reports endpoint.');
        }

        return $dto;
    }

    private function ensureAbuseReport(mixed $value): AbuseReport
    {
        if (! $value instanceof AbuseReport) {
            throw new MightyNetworksException('Expected an AbuseReport from the abuse reports endpoint.');
        }

        return $value;
    }
}
