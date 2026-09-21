<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\PollCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPollData;
use MCKLtech\MightyNetworks\DataTransferObjects\Poll;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePollData;
use MCKLtech\MightyNetworks\Enums\PollType;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\CreatePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\DeletePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\GetPollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\ListPollsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\ReplacePollRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Polls\UpdatePollRequest;
use MCKLtech\MightyNetworks\Resources\PollsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class PollsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function pollPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 11,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'post_type' => 'poll',
            'poll_type' => 'multiple_choice_poll',
            'title' => 'Favourite colour?',
            'description' => 'Pick one',
            'creator' => ['id' => 1],
            'space' => ['id' => 2],
            'images' => [],
            'choices' => [['id' => 'a', 'text' => 'Blue']],
            'status' => 'published',
            'published_at' => '2024-01-16T08:00:00+00:00',
            'permalink' => 'https://example.mn.co/polls/11',
            'comments_enabled' => true,
            'last_activity_at' => '2024-03-21T09:00:00+00:00',
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_poll(): void
    {
        $mock = new MockClient([MockResponse::make($this->pollPayload(), 200)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $poll = $resource->findById(11);

        $this->assertInstanceOf(Poll::class, $poll);
        $this->assertSame(11, $poll->id);
        $this->assertSame('multiple_choice_poll', $poll->pollType);
        $this->assertTrue($poll->commentsEnabled);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPollRequest
                && $request->resolveEndpoint() === 'networks/12345/polls/11/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_all_scopes_to_a_space(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->pollPayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $polls = $resource->all(spaceId: 2);

        $this->assertInstanceOf(PollCollection::class, $polls);
        $this->assertCount(1, $polls);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPollsRequest
                && $request->resolveEndpoint() === 'networks/12345/polls'
                && $request->query()->get('space_id') === 2;
        });
    }

    public function test_create_maps_the_dto_to_a_snake_case_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->pollPayload(), 201)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $resource->create(new NewPollData(
            spaceId: 2,
            title: 'Favourite colour?',
            pollType: PollType::MultipleChoice,
            choices: ['Blue', 'Red'],
            notify: false,
        ));

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreatePollRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/polls'
                && $request->body()->all() === [
                    'space_id' => 2,
                    'title' => 'Favourite colour?',
                    'poll_type' => 'multiple_choice',
                    'choices' => ['Blue', 'Red'],
                    'notify' => false,
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->pollPayload(), 200)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $resource->update(11, new UpdatePollData(title: 'Best colour?'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdatePollRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/polls/11/'
                && $request->body()->all() === ['title' => 'Best colour?'];
        });
    }

    public function test_replace_sends_a_put_with_the_supplied_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->pollPayload(), 200)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $poll = $resource->replace(11, new UpdatePollData(title: 'Replaced'));

        $this->assertSame(11, $poll->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplacePollRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/polls/11/'
                && $request->body()->all() === ['title' => 'Replaced'];
        });
    }

    public function test_delete_sends_a_delete_to_the_poll_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $resource->delete(11);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePollRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/polls/11/';
        });
    }

    public function test_paginate_yields_polls_across_both_documented_envelopes(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->pollPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2],
            ], 200),
            MockResponse::make([
                'data' => [$this->pollPayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2],
            ], 200),
        ]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 1)->items() as $poll) {
            $this->assertInstanceOf(Poll::class, $poll);
            $ids[] = $poll->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Poll not found'], 404)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(11));
    }

    public function test_each_runs_a_callback_over_every_poll(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->pollPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Poll $poll) use (&$ids): void {
            $ids[] = $poll->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Poll not found'], 404)]);

        $resource = new PollsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(11);
    }
}
