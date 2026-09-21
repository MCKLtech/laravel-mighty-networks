<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Webhooks\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use MCKLtech\MightyNetworks\Events\WebhookReceived;
use MCKLtech\MightyNetworks\Webhooks\WebhookEnvelope;

/**
 * Processes a verified webhook delivery off the request path.
 *
 * Mighty Networks retries any non-2xx delivery up to five times over several
 * hours, so identical envelopes can arrive more than once. Idempotency is
 * enforced with an atomic {@see Cache::add()} guard keyed on `event_id`: the
 * first delivery wins and later duplicates are discarded without dispatching
 * {@see WebhookReceived} again.
 *
 * The guard is intentionally a cache lock rather than `ShouldBeUnique` so it
 * also protects deliveries that were already queued (or that were never
 * deduplicated at dispatch time). Tune the window via the cache store's
 * retention; seven days comfortably covers the documented retry horizon.
 */
final class ProcessWebhookJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * How long an `event_id` is remembered as processed, in seconds.
     */
    private const IDEMPOTENCY_TTL_SECONDS = 604800;

    public function __construct(
        public readonly WebhookEnvelope $envelope,
    ) {}

    public function handle(Dispatcher $events): void
    {
        if ($this->envelope->eventId === '') {
            // Without an id there is nothing to de-duplicate against, so every
            // delivery is processed rather than silently dropped.
            $events->dispatch(new WebhookReceived($this->envelope));

            return;
        }

        $key = 'mighty-networks:webhook:'.$this->envelope->eventId;

        if (! Cache::add($key, true, self::IDEMPOTENCY_TTL_SECONDS)) {
            return;
        }

        $events->dispatch(new WebhookReceived($this->envelope));
    }
}
