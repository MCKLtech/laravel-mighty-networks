<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Collections\EventCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Event;
use MCKLtech\MightyNetworks\DataTransferObjects\NewEventData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateEventData;
use MCKLtech\MightyNetworks\Enums\EventFrequency;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;
use MCKLtech\MightyNetworks\Requests\Admin\Events\CreateEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\DeleteEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\GetEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\ListEventsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\ReplaceEventRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Events\UpdateEventRequest;
use MCKLtech\MightyNetworks\Resources\EventsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class EventsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 77,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'post_type' => 'event',
            'creator' => ['id' => 9, 'name' => 'Host'],
            'images' => ['https://cdn.mn.co/events/77.jpg'],
            'permalink' => 'https://example.mn.co/posts/77',
            'title' => 'Monthly Community Meetup',
            'description' => 'Join us',
            'recurrence_rule' => 'FREQ=WEEKLY',
            'rsvp_enabled' => true,
            'rsvp_closed' => false,
            'restricted_event' => false,
            'post_in_feed' => true,
            'event_type' => 'online_meeting',
            'starts_at' => '2024-05-01T18:00:00+00:00',
            'ends_at' => '2024-05-01T19:00:00+00:00',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'Zoom',
            'link' => 'https://zoom.us/j/123',
            'frequency' => 'weekly',
            'interval' => 2,
        ], $overrides);
    }

    private function resource(MockClient $mock): EventsResource
    {
        return new EventsResource($this->admin($mock), '12345');
    }

    public function test_find_by_id_returns_a_typed_event_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->eventPayload(), 200)]);

        $event = $this->resource($mock)->findById(77);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(77, $event->id);
        $this->assertSame('Monthly Community Meetup', $event->title);
        $this->assertSame('online_meeting', $event->eventType);
        $this->assertSame(EventFrequency::Weekly, $event->frequency);
        $this->assertSame('2024-01-15T10:30:00+00:00', $event->createdAt->toIso8601String());

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetEventRequest
                && $request->resolveEndpoint() === 'networks/12345/events/77/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Event not found'], 404)]);

        $this->assertNull($this->resource($mock)->findByIdOrNull(77));
    }

    public function test_all_returns_an_event_collection_for_the_first_page(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->eventPayload(['id' => 1]), $this->eventPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $events = $this->resource($mock)->all(perPage: 50);

        $this->assertInstanceOf(EventCollection::class, $events);
        $this->assertCount(2, $events);
        $this->assertSame([1, 2], $events->map(static fn (Event $event): int => $event->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListEventsRequest
                && $request->resolveEndpoint() === 'networks/12345/events'
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_maps_the_dto_to_a_snake_case_body_and_omits_nulls(): void
    {
        $mock = new MockClient([MockResponse::make($this->eventPayload(['id' => 99]), 201)]);

        $event = $this->resource($mock)->create(new NewEventData(
            title: 'New Event',
            startsAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
            endsAt: CarbonImmutable::parse('2024-05-01T19:00:00+00:00'),
            eventType: 'online_meeting',
            spaceId: 123,
            frequency: EventFrequency::Weekly,
        ));

        $this->assertSame(99, $event->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateEventRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/events'
                && $request->body()->all() === [
                    'title' => 'New Event',
                    'starts_at' => '2024-05-01T18:00:00+00:00',
                    'ends_at' => '2024-05-01T19:00:00+00:00',
                    'event_type' => 'online_meeting',
                    'space_id' => 123,
                    'frequency' => 'weekly',
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->eventPayload(), 200)]);

        $this->resource($mock)->update(77, new UpdateEventData(
            title: 'Renamed',
            startsAt: CarbonImmutable::parse('2024-06-01T18:00:00+00:00'),
        ));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof UpdateEventRequest) {
                return false;
            }

            return $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/events/77/'
                && $request->body()->all() === [
                    'title' => 'Renamed',
                    'starts_at' => '2024-06-01T18:00:00+00:00',
                ];
        });
    }

    public function test_replace_sends_a_put_to_the_event_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make($this->eventPayload(), 200)]);

        $this->resource($mock)->replace(77, new UpdateEventData(title: 'Replaced'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof ReplaceEventRequest
                && $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/events/77/'
                && $request->body()->all() === ['title' => 'Replaced'];
        });
    }

    public function test_delete_sends_a_delete_to_the_event_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $this->resource($mock)->delete(77);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteEventRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/events/77/';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Event not found'], 404)]);

        $this->expectException(NotFoundException::class);

        $this->resource($mock)->findById(77);
    }

    public function test_a_401_throws_an_authentication_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Invalid API token'], 401)]);

        $this->expectException(AuthenticationException::class);

        $this->resource($mock)->findById(77);
    }

    public function test_a_422_throws_a_validation_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Invalid event'], 422)]);

        $this->expectException(ValidationException::class);

        $this->resource($mock)->create(new NewEventData(
            title: 'Bad',
            startsAt: CarbonImmutable::parse('2024-05-01T18:00:00+00:00'),
            endsAt: CarbonImmutable::parse('2024-05-01T19:00:00+00:00'),
            eventType: 'online_meeting',
            spaceId: 1,
        ));
    }

    public function test_it_paginates_the_items_links_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->eventPayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->eventPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $ids = [];

        foreach ($this->resource($mock)->paginate(perPage: 50)->items() as $event) {
            $this->assertInstanceOf(Event::class, $event);
            $ids[] = $event->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request->query()->get('page') === 2
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_it_paginates_the_data_meta_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->eventPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
            MockResponse::make([
                'data' => [$this->eventPayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
        ]);

        $ids = [];

        foreach ($this->resource($mock)->paginate(perPage: 1)->items() as $event) {
            $this->assertInstanceOf(Event::class, $event);
            $ids[] = $event->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_event(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->eventPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $ids = [];

        $this->resource($mock)->each(static function (Event $event) use (&$ids): void {
            $ids[] = $event->id;
        });

        $this->assertSame([1], $ids);
    }
}
