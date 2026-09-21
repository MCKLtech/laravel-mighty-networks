<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use InvalidArgumentException;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMutationPayload;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\ComplexityException;
use MCKLtech\MightyNetworks\Exceptions\ForbiddenException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\Requests\GraphQL\GraphQLMutationRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class GraphQLMutationRequestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function successPayload(array $overrides = []): array
    {
        return array_merge([
            'clientMutationId' => 'req-1',
            'errors' => [],
            'member' => ['id' => 'gid://mighty/Member/1', 'resourceId' => '1', 'name' => 'Jane'],
        ], $overrides);
    }

    public function test_it_builds_a_valid_document_sends_variables_and_hydrates_the_payload(): void
    {
        $mock = new MockClient([
            MockResponse::make(['data' => ['createMember' => $this->successPayload()]], 200),
        ]);

        $selection = Selection::make()
            ->field('clientMutationId')
            ->field('errors')
            ->field('member', selection: Selection::make()->field('id'));

        $request = new GraphQLMutationRequest(
            networkIdOrSubdomain: '12345',
            operation: GraphQLMutation::CreateMember,
            variables: ['input' => ['email' => 'jane@example.com', 'firstName' => 'Jane', 'lastName' => 'Doe']],
            selection: $selection,
        );

        $response = $this->graphql($mock)->send($request);

        $body = $request->body()->all();
        $this->assertSame(Method::POST, $request->getMethod());
        $this->assertSame('networks/12345/graphql', $request->resolveEndpoint());
        $this->assertSame(
            'mutation CreateMember($input: CreateMemberInput!) { createMember(input: $input) '
            .'{ clientMutationId errors member { id } } }',
            $body['query'],
        );
        $this->assertSame(
            ['input' => ['email' => 'jane@example.com', 'firstName' => 'Jane', 'lastName' => 'Doe']],
            $body['variables'],
        );

        $dto = $response->dto();
        $this->assertInstanceOf(GraphQLMutationPayload::class, $dto);
        $this->assertTrue($dto->succeeded());
        $this->assertSame('req-1', $dto->clientMutationId);
        $this->assertSame('Jane', $dto->member()?->name);
    }

    public function test_it_resolves_a_known_raw_operation_string(): void
    {
        $request = new GraphQLMutationRequest('12345', 'CreateMember');

        $this->assertSame('createMember', $request->operation());
        $this->assertSame('CreateMemberInput', $request->inputType());
        $this->assertSame(GraphQLMutation::CreateMember, $request->mutation());
        $this->assertStringContainsString('mutation CreateMember($input: CreateMemberInput!)', $request->document());
    }

    public function test_an_unknown_raw_operation_throws_rather_than_sending_an_invalid_document(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GraphQLMutationRequest('12345', 'totallyMadeUp');
    }

    public function test_an_unknown_raw_operation_is_allowed_with_an_explicit_input_type(): void
    {
        $request = new GraphQLMutationRequest(
            networkIdOrSubdomain: '12345',
            operation: 'brandNewThing',
            variables: ['input' => ['id' => '1']],
            inputType: 'BrandNewThingInput',
        );

        $this->assertNull($request->mutation());
        $this->assertSame('brandNewThing', $request->operation());
        $this->assertSame(
            'mutation BrandNewThing($input: BrandNewThingInput!) { brandNewThing(input: $input) { clientMutationId errors } }',
            $request->document(),
        );
    }

    public function test_the_default_selection_is_the_mutation_envelope(): void
    {
        $request = new GraphQLMutationRequest('12345', GraphQLMutation::BanMember);

        $this->assertSame(
            'mutation BanMember($input: BanMemberInput!) { banMember(input: $input) { clientMutationId errors } }',
            $request->document(),
        );
    }

    public function test_the_client_mutate_helper_sends_the_request(): void
    {
        $mock = new MockClient([
            MockResponse::make(['data' => ['createMember' => $this->successPayload()]], 200),
        ]);

        $client = new GraphQLClient($this->graphql($mock), '12345');

        $response = $client->mutate(
            operation: GraphQLMutation::CreateMember,
            variables: ['input' => ['email' => 'jane@example.com', 'firstName' => 'Jane', 'lastName' => 'Doe']],
        );

        $this->assertTrue($response->dto() instanceof GraphQLMutationPayload);
        $mock->assertSent(static fn ($request): bool => $request instanceof GraphQLMutationRequest
            && $request->operation() === 'createMember');
    }

    /**
     * @return array<string, array{0: class-string<\Throwable>, 1: array<string, mixed>}>
     */
    public static function errorProvider(): array
    {
        return [
            'unauthenticated' => [
                AuthenticationException::class,
                ['message' => 'Unauthenticated.', 'extensions' => ['code' => 'UNAUTHENTICATED']],
            ],
            'forbidden' => [
                ForbiddenException::class,
                ['message' => 'Forbidden.', 'extensions' => ['code' => 'FORBIDDEN']],
            ],
            'not found' => [
                NotFoundException::class,
                ['message' => 'Not found.', 'extensions' => ['code' => 'NOT_FOUND']],
            ],
            'bad user input' => [
                ValidationException::class,
                ['message' => 'Bad input.', 'extensions' => ['code' => 'BAD_USER_INPUT']],
            ],
        ];
    }

    /**
     * @param  class-string<\Throwable>  $expected
     * @param  array<string, mixed>  $error
     */
    #[DataProvider('errorProvider')]
    public function test_graphql_errors_map_to_the_typed_exception(string $expected, array $error): void
    {
        $mock = new MockClient([
            MockResponse::make(['data' => null, 'errors' => [$error]], 200),
        ]);

        $this->expectException($expected);

        $this->graphql($mock)->send(new GraphQLMutationRequest('12345', GraphQLMutation::CreateMember));
    }

    public function test_a_complexity_rejection_maps_to_a_complexity_exception(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => null,
                'errors' => [['message' => 'Mutation complexity exceeds the maximum of 1500.']],
            ], 200),
        ]);

        $this->expectException(ComplexityException::class);

        $this->graphql($mock)->send(new GraphQLMutationRequest('12345', GraphQLMutation::CreateMember));
    }
}
