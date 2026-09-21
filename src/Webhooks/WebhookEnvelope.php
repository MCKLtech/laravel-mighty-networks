<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Webhooks;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;

/**
 * The decoded envelope Mighty Networks POSTs to a webhook endpoint.
 *
 * ```json
 * {
 *   "event_id": "abc123-uuid",
 *   "event_timestamp": "2024-01-15T10:30:00Z",
 *   "event_type": "PostCreated",
 *   "payload": { ... }
 * }
 * ```
 *
 * Both `event_type` and `event_timestamp` are treated defensively: the type is
 * normalised and may be null for an unknown event, and a missing/unparseable
 * timestamp falls back to "now" so a malformed envelope can never throw inside
 * a queued job.
 *
 * Note on member-ish payloads: like the Admin API, a member `email` may be
 *
 * empty or masked (`a***@***.***`) depending on the Network plan. The payload
 * is passed through verbatim; this SDK never attempts to unmask it.
 */
final readonly class WebhookEnvelope
{
    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public string $eventId,
        public CarbonImmutable $eventTimestamp,
        public ?WebhookEventType $eventType,
        public array $payload,
    ) {}

    /**
     * Build an envelope from a decoded JSON payload, tolerating missing or
     * wrong-typed fields.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: self::stringOrEmpty($data['event_id'] ?? null),
            eventTimestamp: self::timestamp($data['event_timestamp'] ?? null),
            eventType: WebhookEventType::fromEventName(self::stringOrEmpty($data['event_type'] ?? null)),
            payload: self::arrayOrEmpty($data['payload'] ?? null),
        );
    }

    private static function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function arrayOrEmpty(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private static function timestamp(mixed $value): CarbonImmutable
    {
        if (is_string($value) && trim($value) !== '') {
            try {
                return CarbonImmutable::parse($value);
            } catch (\Throwable) {
                // Fall through to "now" for a malformed timestamp.
            }
        }

        return CarbonImmutable::now();
    }
}
