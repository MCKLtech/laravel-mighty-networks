<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\SubscriptionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Subscription;
use MCKLtech\MightyNetworks\Enums\CancelTiming;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\DeleteSubscriptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\GetSubscriptionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Subscriptions\ListSubscriptionsRequest;
use MCKLtech\MightyNetworks\Resources\SubscriptionsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class SubscriptionsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function subscriptionPayload(array $overrides = []): array
    {
        return array_merge([
            'member_id' => 42,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'time_zone' => 'America/Los_Angeles',
            'location' => 'San Francisco, CA',
            'referral_count' => 3,
            'avatar' => 'https://cdn.mn.co/avatars/42.jpg',
            'categories' => [],
            'permalink' => 'https://example.mn.co/members/42',
            'ambassador_level' => 'gold',
            'plan' => [
                'id' => 7,
                'name' => 'Founders Circle',
                'amount' => 4900,
                'currency' => 'usd',
                'interval' => 'month',
                'type' => 'subscription',
                'has_free_trial' => true,
            ],
            'subscription' => [
                'id' => 555,
                'current_period_start' => '2024-03-01T00:00:00+00:00',
                'current_period_end' => '2024-04-01T00:00:00+00:00',
                'payment_platform' => 'stripe',
                'canceled_at' => null,
                'trial_length' => 14,
                'trial_start' => '2024-02-15T00:00:00+00:00',
                'trial_end' => '2024-02-29T00:00:00+00:00',
                'purchased_at' => '2024-02-15T00:00:00+00:00',
            ],
        ], $overrides);
    }

    public function test_all_returns_a_subscription_collection_and_sends_filters(): void
    {
        $mock = new MockClient([
            MockResponse::make(['items' => [$this->subscriptionPayload()], 'links' => ['next' => null]], 200),
        ]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $subscriptions = $resource->all(status: 'canceled', memberId: 42, perPage: 10);

        $this->assertInstanceOf(SubscriptionCollection::class, $subscriptions);
        $this->assertCount(1, $subscriptions);
        $this->assertSame(4900, $subscriptions->first()?->plan->amount);

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListSubscriptionsRequest
                && $request->resolveEndpoint() === 'networks/12345/subscriptions'
                && $request->query()->get('status') === 'canceled'
                && $request->query()->get('member_id') === 42
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_find_by_id_uses_no_trailing_slash(): void
    {
        $mock = new MockClient([MockResponse::make($this->subscriptionPayload(), 200)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $subscription = $resource->findById(555);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertSame(555, $subscription->subscription->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetSubscriptionRequest
                && $request->getMethod() === Method::GET
                && $request->resolveEndpoint() === 'networks/12345/subscriptions/555';
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(555));
    }

    public function test_cancel_sends_the_when_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->subscriptionPayload(), 200)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $subscription = $resource->cancel(555, CancelTiming::Now);

        $this->assertInstanceOf(Subscription::class, $subscription);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteSubscriptionRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/subscriptions/555'
                && $request->query()->get('when') === 'now';
        });
    }

    public function test_cancel_omits_when_when_not_provided(): void
    {
        $mock = new MockClient([MockResponse::make($this->subscriptionPayload(), 200)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $resource->cancel(555);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteSubscriptionRequest
                && $request->query()->get('when') === null;
        });
    }

    public function test_delete_is_an_alias_for_cancel(): void
    {
        $mock = new MockClient([MockResponse::make($this->subscriptionPayload(), 200)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $subscription = $resource->delete(555, 'end_of_billing_cycle');

        $this->assertSame(555, $subscription->subscription->id);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteSubscriptionRequest
                && $request->query()->get('when') === 'end_of_billing_cycle';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(555);
    }

    public function test_it_paginates_subscriptions_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->subscriptionPayload(['member_id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2],
            ], 200),
            MockResponse::make([
                'data' => [$this->subscriptionPayload(['member_id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2],
            ], 200),
        ]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $memberIds = [];

        foreach ($resource->paginate(perPage: 1)->items() as $subscription) {
            $this->assertInstanceOf(Subscription::class, $subscription);
            $memberIds[] = $subscription->memberId;
        }

        $this->assertSame([1, 2], $memberIds);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_subscription(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->subscriptionPayload()],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new SubscriptionsResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Subscription $subscription) use (&$ids): void {
            $ids[] = $subscription->subscription->id;
        });

        $this->assertSame([555], $ids);
    }
}
