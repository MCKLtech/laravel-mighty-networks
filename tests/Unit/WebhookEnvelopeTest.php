<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;
use MCKLtech\MightyNetworks\Webhooks\WebhookEnvelope;
use PHPUnit\Framework\TestCase;

final class WebhookEnvelopeTest extends TestCase
{
    public function test_from_array_maps_every_field(): void
    {
        $envelope = WebhookEnvelope::fromArray([
            'event_id' => 'abc123-uuid',
            'event_timestamp' => '2024-01-15T10:30:00Z',
            'event_type' => 'PostCreated',
            'payload' => ['id' => 12345, 'title' => 'Example post title'],
        ]);

        $this->assertSame('abc123-uuid', $envelope->eventId);
        $this->assertSame('2024-01-15T10:30:00+00:00', $envelope->eventTimestamp->toIso8601String());
        $this->assertSame(WebhookEventType::PostCreated, $envelope->eventType);
        $this->assertSame(['id' => 12345, 'title' => 'Example post title'], $envelope->payload);
    }

    public function test_an_unknown_event_type_is_null_and_does_not_throw(): void
    {
        $envelope = WebhookEnvelope::fromArray([
            'event_id' => 'id',
            'event_timestamp' => '2024-01-15T10:30:00Z',
            'event_type' => 'SomethingBrandNew',
            'payload' => [],
        ]);

        $this->assertNull($envelope->eventType);
    }

    public function test_it_accepts_a_graphql_hook_style_event_name(): void
    {
        $envelope = WebhookEnvelope::fromArray([
            'event_id' => 'id',
            'event_timestamp' => '2024-01-15T10:30:00Z',
            'event_type' => 'MemberJoinedHook',
        ]);

        $this->assertSame(WebhookEventType::MemberJoined, $envelope->eventType);
    }

    public function test_a_missing_or_malformed_timestamp_falls_back_to_now(): void
    {
        $before = CarbonImmutable::now()->subSecond();

        $envelope = WebhookEnvelope::fromArray([
            'event_id' => '',
            'event_timestamp' => 'not-a-date',
        ]);

        $this->assertTrue($envelope->eventTimestamp->greaterThanOrEqualTo($before));
        $this->assertSame('', $envelope->eventId);
        $this->assertSame([], $envelope->payload);
    }

    public function test_a_non_array_payload_becomes_an_empty_array(): void
    {
        $envelope = WebhookEnvelope::fromArray([
            'event_id' => 'id',
            'event_timestamp' => '2024-01-15T10:30:00Z',
            'payload' => 'truncated',
        ]);

        $this->assertSame([], $envelope->payload);
    }

    public function test_from_array_tolerates_a_completely_empty_payload(): void
    {
        $envelope = WebhookEnvelope::fromArray([]);

        $this->assertSame('', $envelope->eventId);
        $this->assertNull($envelope->eventType);
        $this->assertSame([], $envelope->payload);
    }
}
