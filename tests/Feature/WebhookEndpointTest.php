<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use MCKLtech\MightyNetworks\Events\WebhookReceived;
use MCKLtech\MightyNetworks\Tests\TestCase;
use MCKLtech\MightyNetworks\Webhooks\Jobs\ProcessWebhookJob;
use MCKLtech\MightyNetworks\Webhooks\Middleware\VerifyWebhookSecret;
use MCKLtech\MightyNetworks\Webhooks\WebhookController;
use MCKLtech\MightyNetworks\Webhooks\WebhookEnvelope;

final class WebhookEndpointTest extends TestCase
{
    private const SECRET = 'test-webhook-secret-value';

    private const URL = '/webhooks/mighty-networks';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('mighty-networks.webhooks.enabled', true);
        $app['config']->set('mighty-networks.webhooks.path', 'webhooks/mighty-networks');
        $app['config']->set('mighty-networks.webhooks.secret', self::SECRET);
    }

    protected function tearDown(): void
    {
        VerifyWebhookSecret::resolveSecretUsing(null);
        WebhookController::resolveNetworkUsing(null);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'event_id' => 'abc123-uuid',
            'event_timestamp' => CarbonImmutable::now()->toIso8601String(),
            'event_type' => 'PostCreated',
            'payload' => ['id' => 12345, 'title' => 'Example post title'],
        ], $overrides);
    }

    public function test_a_valid_secret_and_fresh_timestamp_dispatches_the_job_and_acks(): void
    {
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload());

        $response->assertStatus(202);
        $response->assertJson(['status' => 'accepted', 'event_id' => 'abc123-uuid']);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job): bool {
            return $job->envelope->eventId === 'abc123-uuid'
                && $job->envelope->eventType?->value === 'POST_CREATED';
        });
    }

    public function test_a_wrong_secret_is_rejected_and_does_not_leak_the_secret(): void
    {
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer wrong-secret')
            ->postJson(self::URL, $this->payload());

        $response->assertStatus(403);
        $response->assertDontSee(self::SECRET, false);
        Queue::assertNothingPushed();
    }

    public function test_a_secret_differing_only_in_the_last_character_is_rejected(): void
    {
        Queue::fake();

        // hash_equals() is constant-time, so a near-match reveals nothing; the
        // delivery is still flatly rejected.
        $nearMatch = substr(self::SECRET, 0, -1).'X';

        $this->withHeader('Authorization', 'Bearer '.$nearMatch)
            ->postJson(self::URL, $this->payload())
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_a_missing_authorization_header_is_rejected(): void
    {
        Queue::fake();

        $this->postJson(self::URL, $this->payload())->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_a_non_bearer_authorization_header_is_rejected(): void
    {
        Queue::fake();

        $this->withHeader('Authorization', 'Basic '.self::SECRET)
            ->postJson(self::URL, $this->payload())
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_a_stale_timestamp_beyond_tolerance_is_rejected(): void
    {
        Queue::fake();
        config()->set('mighty-networks.webhooks.tolerance', 300);

        $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload([
                'event_timestamp' => CarbonImmutable::now()->subHour()->toIso8601String(),
            ]))
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_a_future_timestamp_beyond_tolerance_is_rejected(): void
    {
        Queue::fake();
        config()->set('mighty-networks.webhooks.tolerance', 300);

        $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload([
                'event_timestamp' => CarbonImmutable::now()->addHour()->toIso8601String(),
            ]))
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_an_unknown_event_type_is_still_acknowledged_without_a_500(): void
    {
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload(['event_type' => 'SomethingBrandNew']));

        $response->assertStatus(202);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job): bool {
            return $job->envelope->eventType === null;
        });
    }

    public function test_a_truncated_or_invalid_json_body_does_not_500(): void
    {
        Queue::fake();

        $response = $this->call(
            'POST',
            self::URL,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.self::SECRET,
            ],
            '{"event_id": ',
        );

        // The verifier refuses a body that is not a JSON object, so this is a
        // 403 rather than a 500 -- the important part is that it is not a
        // server error.
        $response->assertStatus(403);
        Queue::assertNothingPushed();
    }

    public function test_a_well_formed_but_non_array_payload_is_handled_without_a_500(): void
    {
        Queue::fake();

        $response = $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload(['payload' => 'truncated']));

        $response->assertStatus(202);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job): bool {
            return $job->envelope->payload === [];
        });
    }

    public function test_the_same_event_id_is_only_processed_once(): void
    {
        Event::fake();

        $envelope = WebhookEnvelope::fromArray($this->payload());

        // The sync queue connection runs each job immediately, so the cache
        // guard is exercised end to end.
        dispatch(new ProcessWebhookJob($envelope));
        dispatch(new ProcessWebhookJob($envelope));

        Event::assertDispatchedTimes(WebhookReceived::class, 1);
    }

    public function test_distinct_event_ids_are_both_processed(): void
    {
        Event::fake();

        dispatch(new ProcessWebhookJob(WebhookEnvelope::fromArray($this->payload(['event_id' => 'first']))));
        dispatch(new ProcessWebhookJob(WebhookEnvelope::fromArray($this->payload(['event_id' => 'second']))));

        Event::assertDispatchedTimes(WebhookReceived::class, 2);
    }

    public function test_timestamps_are_not_checked_by_default_so_late_retries_are_accepted(): void
    {
        Queue::fake();

        $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload([
                'event_timestamp' => CarbonImmutable::now()->subHours(6)->toIso8601String(),
            ]))
            ->assertStatus(202);

        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_a_secret_resolver_gives_each_network_its_own_secret(): void
    {
        Queue::fake();

        $secrets = [1 => 'secret-for-network-one', 2 => 'secret-for-network-two'];

        VerifyWebhookSecret::resolveSecretUsing(
            static fn (Request $request): ?string => $secrets[$request->json('payload.network_id')] ?? null,
        );

        $this->withHeader('Authorization', 'Bearer secret-for-network-two')
            ->postJson(self::URL, $this->payload(['payload' => ['network_id' => 2]]))
            ->assertStatus(202);

        // Network one's secret does not open network two's deliveries.
        $this->withHeader('Authorization', 'Bearer secret-for-network-one')
            ->postJson(self::URL, $this->payload(['payload' => ['network_id' => 2]]))
            ->assertStatus(403);

        Queue::assertPushed(ProcessWebhookJob::class, 1);
    }

    public function test_a_secret_resolver_replaces_the_configured_secret(): void
    {
        Queue::fake();

        VerifyWebhookSecret::resolveSecretUsing(static fn (Request $request): ?string => null);

        $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload())
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_the_job_carries_the_network_id_from_the_payload(): void
    {
        Queue::fake();

        $this->withHeader('Authorization', 'Bearer '.self::SECRET)
            ->postJson(self::URL, $this->payload(['payload' => ['network_id' => 90000823]]))
            ->assertStatus(202);

        Queue::assertPushed(ProcessWebhookJob::class, fn (ProcessWebhookJob $job): bool => $job->network === '90000823');
    }

    public function test_a_network_resolver_names_the_network_when_the_payload_does_not(): void
    {
        Queue::fake();

        WebhookController::resolveNetworkUsing(
            static fn (Request $request, WebhookEnvelope $envelope): ?string => $request->header('X-Network'),
        );

        // Comment payloads carry no network_id.
        $this->withHeaders(['Authorization' => 'Bearer '.self::SECRET, 'X-Network' => 'acme'])
            ->postJson(self::URL, $this->payload(['event_type' => 'CommentCreatedHook']))
            ->assertStatus(202);

        Queue::assertPushed(ProcessWebhookJob::class, fn (ProcessWebhookJob $job): bool => $job->network === 'acme');
    }

    public function test_the_same_event_id_from_two_networks_is_processed_for_each(): void
    {
        $networks = [];
        Event::listen(WebhookReceived::class, function (WebhookReceived $event) use (&$networks): void {
            $networks[] = $event->network;
        });

        $envelope = WebhookEnvelope::fromArray($this->payload());

        dispatch(new ProcessWebhookJob($envelope, 'one'));
        dispatch(new ProcessWebhookJob($envelope, 'two'));
        dispatch(new ProcessWebhookJob($envelope, 'two'));

        $this->assertSame(['one', 'two'], $networks);
    }

    public function test_an_event_whose_listener_fails_is_processed_again_on_retry(): void
    {
        $attempts = 0;
        Event::listen(WebhookReceived::class, function () use (&$attempts): void {
            if (++$attempts === 1) {
                throw new \RuntimeException('listener failed');
            }
        });

        $envelope = WebhookEnvelope::fromArray($this->payload());

        try {
            dispatch(new ProcessWebhookJob($envelope));
            $this->fail('The listener failure should reach the queue so the job is retried.');
        } catch (\RuntimeException $e) {
            $this->assertSame('listener failed', $e->getMessage());
        }

        dispatch(new ProcessWebhookJob($envelope));
        dispatch(new ProcessWebhookJob($envelope));

        // Failed once, succeeded on the retry, then skipped as a duplicate.
        $this->assertSame(2, $attempts);
    }
}
