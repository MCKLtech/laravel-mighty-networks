<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Pagination\Contracts;

use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * Marks a GraphQL request as cursor-paginatable and tells the paginator where
 * the Relay connection lives inside the `data` object.
 *
 * For example, a root query returning `data.members` should return `members`;
 * a field nested inside another connection might return `network.members`.
 */
interface GraphQLCursorPaginatable extends Paginatable
{
    /**
     * The dotted path, relative to `data`, of the connection to paginate.
     */
    public function getCursorPath(): string;
}
