<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Exceptions;

use Saloon\Http\Response;
use Throwable;

/**
 * Thrown when the GraphQL endpoint returns a non-empty top-level `errors`
 * array. GraphQL reports these with HTTP 200, so this exception is the only
 * reliable signal that a query failed.
 */
final class GraphQLException extends RequestException
{
    /**
     * @param  list<array<string, mixed>>  $errors
     */
    public function __construct(
        Response $response,
        private readonly array $errors = [],
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        if ($message === null && $errors !== []) {
            $first = $errors[0]['message'] ?? null;
            $message = is_string($first) ? 'GraphQL error: '.$first : 'GraphQL error.';
        }

        parent::__construct($response, $message, $code, $previous);
    }

    /**
     * Build the exception from a raw GraphQL response.
     */
    public static function fromResponse(Response $response): self
    {
        return new self($response, self::extractErrors($response));
    }

    /**
     * The top-level GraphQL errors carried by the response.
     *
     * @return list<array<string, mixed>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Decode and normalise the `errors` array from a response body.
     *
     * @return list<array<string, mixed>>
     */
    public static function extractErrors(Response $response): array
    {
        $decoded = json_decode($response->body(), true);

        if (! is_array($decoded) || ! isset($decoded['errors']) || ! is_array($decoded['errors'])) {
            return [];
        }

        $errors = [];

        foreach ($decoded['errors'] as $error) {
            if (is_array($error)) {
                /** @var array<string, mixed> $error */
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
