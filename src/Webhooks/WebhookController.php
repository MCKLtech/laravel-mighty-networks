<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MCKLtech\MightyNetworks\Webhooks\Jobs\ProcessWebhookJob;

/**
 * Receives a verified Mighty Networks webhook delivery.
 *
 * Deliberately minimal: authentication already happened in
 * {@see Middleware\VerifyWebhookSecret}. The envelope is decoded, a queued job
 * is dispatched and a fast 2xx acknowledgement is returned so the delivery is
 * not retried. No business logic runs here.
 */
final class WebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $envelope = WebhookEnvelope::fromArray($this->decode($request));

        dispatch(new ProcessWebhookJob($envelope));

        return response()->json(
            ['status' => 'accepted', 'event_id' => $envelope->eventId],
            202,
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
