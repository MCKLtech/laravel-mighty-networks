<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Pagination;

use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;

/**
 * Paginator for the Admin REST API.
 *
 * The API documents two response envelopes, and both are supported:
 *
 * - `{ "items": [...], "links": { "self": ..., "next": ... } }`
 * - `{ "data": [...], "meta": { "current_page": ..., "total_pages": ... } }`
 *
 * The end of the result set is signalled by an absent/empty `links.next`, or by
 * `meta.current_page` reaching `meta.total_pages`.
 */
final class AdminPagedPaginator extends PagedPaginator
{
    /**
     * Apply the `page`/`per_page` query parameters.
     */
    #[\Override]
    protected function applyPagination(Request $request): Request
    {
        $request->query()->add('page', $this->page);

        if ($this->perPageLimit !== null) {
            $request->query()->add('per_page', $this->perPageLimit);
        }

        return $request;
    }

    /**
     * Determine whether the current page is the last one.
     */
    #[\Override]
    protected function isLastPage(Response $response): bool
    {
        $data = $this->decode($response);

        if ($data === null) {
            return true;
        }

        if (is_array($data['links'] ?? null)) {
            $next = $data['links']['next'] ?? null;

            return ! is_string($next) || trim($next) === '';
        }

        $meta = $data['meta'] ?? null;

        if (is_array($meta)) {
            $current = $meta['current_page'] ?? null;
            $total = $meta['total_pages'] ?? null;

            if (is_numeric($current) && is_numeric($total)) {
                return (int) $current >= (int) $total;
            }
        }

        return true;
    }

    /**
     * Extract the page items from either documented envelope.
     *
     * @return array<mixed, mixed>
     */
    #[\Override]
    protected function getPageItems(Response $response, Request $request): array
    {
        $data = $this->decode($response);

        if ($data === null) {
            return [];
        }

        foreach (['items', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(Response $response): ?array
    {
        $decoded = json_decode($response->body(), true);

        return is_array($decoded) ? $decoded : null;
    }
}
