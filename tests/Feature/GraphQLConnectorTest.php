<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\ComplexityException;
use MCKLtech\MightyNetworks\Exceptions\GraphQLException;
use MCKLtech\MightyNetworks\Exceptions\RateLimitException;
use MCKLtech\MightyNetworks\Tests\Support\TestGraphQLRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class GraphQLConnectorTest extends TestCase
{
    public function test_it_sends_the_mandatory_user_agent_and_bearer_token(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $this->graphql($mock)->send(new TestGraphQLRequest);

        $mock->assertSent(function ($request, $response): bool {
            $pending = $response->getPendingRequest();

            return $pending->headers()->get('User-Agent') === 'laravel-mighty-networks-tests/1.0'
                && $pending->headers()->get('Authorization') === 'Bearer test-access-token';
        });
    }

    public function test_a_http_200_with_errors_is_treated_as_a_failure(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => null,
                'errors' => [[
                    'message' => 'Something went wrong',
                    'path' => ['members'],
                    'extensions' => ['code' => 'INTERNAL_SERVER_ERROR'],
                ]],
            ], 200),
        ]);

        $connector = $this->graphql($mock);

        try {
            $connector->send(new TestGraphQLRequest);

            $this->fail('Expected a GraphQLException to be thrown.');
        } catch (GraphQLException $exception) {
            $this->assertCount(1, $exception->errors());
            $this->assertSame('INTERNAL_SERVER_ERROR', $exception->errors()[0]['extensions']['code']);
            $this->assertSame(200, $exception->getResponse()->status());
        }
    }

    public function test_an_unauthenticated_error_maps_to_an_authentication_exception(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => null,
                'errors' => [[
                    'message' => 'Unauthenticated.',
                    'extensions' => ['code' => 'UNAUTHENTICATED'],
                ]],
            ], 200),
        ]);

        $this->expectException(AuthenticationException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_throttled_error_maps_to_a_rate_limit_exception(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => null,
                'errors' => [[
                    'message' => 'Too many requests.',
                    'extensions' => ['code' => 'THROTTLED'],
                ]],
            ], 200),
        ]);

        $this->expectException(RateLimitException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_complexity_rejection_maps_to_a_complexity_exception(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => null,
                'errors' => [[
                    'message' => 'Query complexity exceeds the maximum of 1500.',
                ]],
            ], 200),
        ]);

        $this->expectException(ComplexityException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_http_200_without_errors_is_successful(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $response = $this->graphql($mock)->send(new TestGraphQLRequest);

        $this->assertTrue($response->successful());
    }

    public function test_it_paginates_a_relay_cursor_connection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [
                    'members' => [
                        'nodes' => [['id' => 1], ['id' => 2]],
                        'pageInfo' => ['endCursor' => 'CURSOR1', 'hasNextPage' => true],
                    ],
                ],
            ], 200),
            MockResponse::make([
                'data' => [
                    'members' => [
                        'nodes' => [['id' => 3]],
                        'pageInfo' => ['endCursor' => 'CURSOR2', 'hasNextPage' => false],
                    ],
                ],
            ], 200),
        ]);

        $connector = $this->graphql($mock);

        $items = [];

        foreach ($connector->paginate(new TestGraphQLRequest)->setPerPageLimit(50)->items() as $item) {
            $items[] = $item;
        }

        $this->assertCount(3, $items);
        $mock->assertSentCount(2);

        $requests = array_map(
            static fn ($response) => $response->getPendingRequest()->getRequest(),
            $mock->getRecordedResponses(),
        );

        $firstBody = $requests[0]->body()->all();
        $secondBody = $requests[1]->body()->all();

        $this->assertSame(50, $firstBody['variables']['first']);
        $this->assertArrayNotHasKey('after', $firstBody['variables']);
        $this->assertSame('CURSOR1', $secondBody['variables']['after']);
    }
}
