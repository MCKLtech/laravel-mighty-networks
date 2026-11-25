<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Events;

use MCKLtech\MightyNetworks\Webhooks\Jobs\ProcessWebhookJob;
use MCKLtech\MightyNetworks\Webhooks\WebhookController;
use MCKLtech\MightyNetworks\Webhooks\WebhookEnvelope;

/**
 * Dispatched by {@see ProcessWebhookJob}
 * once a verified delivery has been accepted and de-duplicated.
 *
 * Consumer applications listen for this event rather than registering 47
 * listeners; branch on {@see WebhookEnvelope::$eventType} when the handling
 * differs by type.
 *
 * `$network` is the network the delivery belongs to, when it is known: the
 * payload's `network_id`, or whatever
 * {@see WebhookController::resolveNetworkUsing()}
 * returned. Some payloads (comments, for one) carry no `network_id`.
 *
 * A listener that throws fails the job, and the delivery is processed again
 * when the job is retried or Mighty Networks sends it again. Every listener
 * then runs again, including those that succeeded, so listeners should be
 * idempotent.
 */
final class WebhookReceived
{
    public function __construct(
        public readonly WebhookEnvelope $envelope,
        public readonly ?string $network = null,
    ) {}
}
