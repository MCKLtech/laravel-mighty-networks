<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Base class for Mighty API GraphQL requests.
 *
 * Every operation is a `POST` to `networks/{network_id_or_subdomain}/graphql`
 * with a JSON body of `{ "query": ..., "variables": ... }`. GraphQL reports
 * application-level failures with HTTP 200 and a non-empty top-level `errors`
 * array; {@see GraphQLConnector} turns that
 * into the SDK's exception hierarchy, so request classes only ever see `data`.
 */
abstract class GraphQLRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly int|string $networkIdOrSubdomain,
    ) {}

    /**
     * The GraphQL document to execute.
     */
    abstract public function document(): string;

    /**
     * Variables referenced by the document. Omitted from the body when empty.
     *
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return sprintf('networks/%s/graphql', $this->networkIdOrSubdomain);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $body = ['query' => $this->document()];

        $variables = $this->variables();

        if ($variables !== []) {
            $body['variables'] = $variables;
        }

        return $body;
    }

    /**
     * The decoded `data` object, or an empty array when the response carried none.
     *
     * @return array<string, mixed>
     */
    protected function dataFrom(Response $response): array
    {
        $decoded = $response->json();

        if (! is_array($decoded)) {
            return [];
        }

        $data = $decoded['data'] ?? null;

        return is_array($data) ? $data : [];
    }
}
