<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Pagination;

use Saloon\Contracts\Body\HasBody;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\CursorPaginator;

/**
 * Paginator for the GraphQL API's Relay cursor connections.
 *
 * Connections expose `edges { cursor node }`, `nodes` and
 * `pageInfo { endCursor hasNextPage }`. Pagination is driven by the `after`
 * variable (and `first` for the page size) carried in the GraphQL body.
 */
final class GraphQLCursorPaginator extends CursorPaginator
{
    /**
     * @param  string  $cursorPath  Dotted path to the connection, relative to `data`.
     */
    public function __construct(
        Connector $connector,
        Request $request,
        private readonly string $cursorPath = '',
    ) {
        parent::__construct($connector, $request);
    }

    /**
     * Add the `after`/`first` variables to the GraphQL request body.
     */
    #[\Override]
    protected function applyPagination(Request $request): Request
    {
        if (! $request instanceof HasBody) {
            return $request;
        }

        $variables = [];

        if ($this->currentResponse instanceof Response) {
            $cursor = $this->getNextCursor($this->currentResponse);

            if ($cursor !== '') {
                $variables['after'] = $cursor;
            }
        }

        if ($this->perPageLimit !== null) {
            $variables['first'] = $this->perPageLimit;
        }

        if ($variables === []) {
            return $request;
        }

        $body = $request->body()->all();

        if (! is_array($body)) {
            $body = [];
        }

        $existing = $body['variables'] ?? null;

        /** @var array<string, mixed> $existing */
        $existing = is_array($existing) ? $existing : [];

        $body['variables'] = array_merge($existing, $variables);

        $request->body()->set($body);

        return $request;
    }

    /**
     * Read the next cursor from `pageInfo.endCursor`.
     */
    #[\Override]
    protected function getNextCursor(Response $response): int|string
    {
        $cursor = $this->getPath($response, 'pageInfo.endCursor');

        return is_int($cursor) || is_string($cursor) ? $cursor : '';
    }

    /**
     * The Relay connection signals the end with `pageInfo.hasNextPage = false`.
     */
    #[\Override]
    protected function isLastPage(Response $response): bool
    {
        return $this->getPath($response, 'pageInfo.hasNextPage') !== true;
    }

    /**
     * Unwrap `data.<path>.nodes`, falling back to mapping `edges` to their nodes.
     *
     * @return array<mixed, mixed>
     */
    #[\Override]
    protected function getPageItems(Response $response, Request $request): array
    {
        $nodes = $this->getPath($response, 'nodes');

        if (is_array($nodes)) {
            return $nodes;
        }

        $edges = $this->getPath($response, 'edges');

        if (! is_array($edges)) {
            return [];
        }

        $items = [];

        foreach ($edges as $edge) {
            if (is_array($edge) && array_key_exists('node', $edge)) {
                $items[] = $edge['node'];
            }
        }

        return $items;
    }

    /**
     * Resolve a dotted path beneath `data` (and the configured connection path).
     */
    private function getPath(Response $response, string $suffix): mixed
    {
        $path = $this->cursorPath === ''
            ? 'data.'.$suffix
            : 'data.'.$this->cursorPath.'.'.$suffix;

        return data_get($response->json(), $path);
    }
}
