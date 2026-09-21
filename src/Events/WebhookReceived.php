<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Events;

use MCKLtech\MightyNetworks\Webhooks\Jobs\ProcessWebhookJob;
use MCKLtech\MightyNetworks\Webhooks\WebhookEnvelope;

/**
 * Dispatched by {@see ProcessWebhookJob}
 * once a verified delivery has been accepted and de-duplicated.
 *
 * Consumer applications listen for this event rather than registering 47
 * listeners; branch on {@see WebhookEnvelope::$eventType} when the handling
 * differs by type.
 */
final class WebhookReceived
{
    public function __construct(
        public readonly WebhookEnvelope $envelope,
    ) {}
}
