<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Collections\RsvpCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\NewRsvpData;
use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateRsvpData;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\CreateRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\DeleteRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\GetRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\ListRsvpsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\ReplaceRsvpRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Rsvps\UpdateRsvpRequest;
use MCKLtech\MightyNetworks\Resources\EventsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class RsvpsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rsvpPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 5,
            'updated' => '2024-05-01T18:05:00+00:00',
            'status' => 'maybe',
            'event' => ['id' => 77, 'title' => 'Meetup'],
            'member' => ['id' => 42, 'first_name' => 'Jane'],
            'event_instance' => [
                'post_id' => 77,
                'starts_at' => '2024-05-01T18:00:00+00:00',
                'ends_at' => '2024-05-01T19:00:00+00:00',
                'instance_index' => 0,
            ],
        ], $overrides);
    }

    private function resource(MockClient $mock): EventsResource
    {
        return new EventsResource($this->admin($mock), '12345');
    }

    public function test_rsvps_returns_a_typed_collection_with_the_instance_filter(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->rsvpPayload(['id' => 1]), $this->rsvpPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $rsvps = $this->resource($mock)->rsvps(77, '2024-05-01T18:00:00+00:00');

        $this->assertInstanceOf(RsvpCollection::class, $rsvps);
        $this->assertCount(2, $rsvps);
        $this->assertSame([1, 2], $rsvps->map(static fn (Rsvp $rsvp): int => $rsvp->id)->all());
        $this->assertSame(RsvpStatus::Maybe, $rsvps->first()?->status);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListRsvpsRequest
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps'
                && $request->query()->get('instance_at') === '2024-05-01T18:00:00+00:00';
        });
    }

    public function test_rsvps_accepts_a_carbon_instance_filter(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [], 'links' => ['next' => null]], 200),
        ]);

        $this->resource($mock)->rsvps(77, CarbonImmutable::parse('2024-05-01T18:00:00+00:00'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListRsvpsRequest
                && $request->query()->get('instance_at') === '2024-05-01T18:00:00+00:00';
        });
    }

    public function test_rsvp_returns_a_single_rsvp_with_the_trailing_slash_route(): void
    {
        $mock = new MockClient([MockResponse::make($this->rsvpPayload(), 200)]);

        $rsvp = $this->resource($mock)->rsvp(77, 5);

        $this->assertInstanceOf(Rsvp::class, $rsvp);
        $this->assertSame(5, $rsvp->id);
        $this->assertSame(77, $rsvp->eventInstance['post_id'] ?? null);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetRsvpRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps/5/';
        });
    }

    public function test_rsvp_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'RSVP not found'], 404)]);

        $this->assertNull($this->resource($mock)->rsvpOrNull(77, 5));
    }

    public function test_create_rsvp_posts_the_dto_as_a_snake_case_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->rsvpPayload(['id' => 9]), 200)]);

        $rsvp = $this->resource($mock)->createRsvp(77, new NewRsvpData(
            memberId: 42,
            status: RsvpStatus::Yes,
            instanceAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
        ));

        $this->assertSame(9, $rsvp->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateRsvpRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps'
                && $request->body()->all() === [
                    'member_id' => 42,
                    'status' => 'yes',
                    'instance_at' => '2024-05-01T18:00:00+00:00',
                ];
        });
    }

    public function test_update_rsvp_sends_a_patch(): void
    {
        $mock = new MockClient([MockResponse::make($this->rsvpPayload(), 200)]);

        $this->resource($mock)->updateRsvp(77, 5, new UpdateRsvpData(status: RsvpStatus::No));

        $mock->assertSent(function ($request): bool {
            return $request instanceof UpdateRsvpRequest
                && $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps/5/'
                && $request->body()->all() === ['status' => 'no'];
        });
    }

    public function test_replace_rsvp_sends_a_put(): void
    {
        $mock = new MockClient([MockResponse::make($this->rsvpPayload(), 200)]);

        $this->resource($mock)->replaceRsvp(77, 5, new UpdateRsvpData(
            memberId: 42,
            status: RsvpStatus::Maybe,
        ));

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceRsvpRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps/5/'
                && $request->body()->all() === ['member_id' => 42, 'status' => 'maybe'];
        });
    }

    public function test_delete_rsvp_sends_a_delete(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $this->resource($mock)->deleteRsvp(77, 5);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteRsvpRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/events/77/rsvps/5/';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'RSVP not found'], 404)]);

        $this->expectException(NotFoundException::class);

        $this->resource($mock)->rsvp(77, 5);
    }

    public function test_it_paginates_rsvps_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->rsvpPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->rsvpPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $ids = [];

        foreach ($this->resource($mock)->paginateRsvps(77, perPage: 50)->items() as $rsvp) {
            $this->assertInstanceOf(Rsvp::class, $rsvp);
            $ids[] = $rsvp->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request->query()->get('page') === 2
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_each_rsvp_runs_a_callback_over_every_rsvp(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->rsvpPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $ids = [];

        $this->resource($mock)->eachRsvp(77, static function (Rsvp $rsvp) use (&$ids): void {
            $ids[] = $rsvp->id;
        });

        $this->assertSame([1], $ids);
    }
}
