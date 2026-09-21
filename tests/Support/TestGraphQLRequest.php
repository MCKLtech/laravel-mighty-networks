<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Support;

use MCKLtech\MightyNetworks\Pagination\Contracts\GraphQLCursorPaginatable;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class TestGraphQLRequest extends Request implements GraphQLCursorPaginatable, HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    #[\Override]
    public function resolveEndpoint(): string
    {
        return 'networks/12345/graphql';
    }

    #[\Override]
    public function getCursorPath(): string
    {
        return 'members';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'query' => 'query ($first: Int, $after: String) { members(first: $first, after: $after) { nodes { id } } }',
            'variables' => ['first' => 25],
        ];
    }
}
