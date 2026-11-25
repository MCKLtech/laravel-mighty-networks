<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Webhooks;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MCKLtech\MightyNetworks\Events\WebhookReceived;
use MCKLtech\MightyNetworks\Webhooks\Jobs\ProcessWebhookJob;

/**
 * Receives a verified Mighty Networks webhook delivery.
 *
 * Deliberately minimal: authentication already happened in
 * {@see Middleware\VerifyWebhookSecret}. The envelope is decoded, a queued job
 * is dispatched and a fast 2xx acknowledgement is returned so the delivery is
 * not retried. No business logic runs here.
 *
 * The network a delivery belongs to is read from `payload.network_id`. Not
 * every payload has one, so an application serving several networks can name
 * it another way with {@see self::resolveNetworkUsing()}.
 */
final class WebhookController
{
    /**
     * @var (Closure(Request, WebhookEnvelope): (string|int|null))|null
     */
    private static ?Closure $networkResolver = null;

    /**
     * Decide which network a delivery belongs to, for example from a route
     * parameter. The result scopes the idempotency key and is passed to
     * {@see WebhookReceived}. Pass null to go
     * back to reading `payload.network_id`.
     *
     * @param  (Closure(Request, WebhookEnvelope): (string|int|null))|null  $resolver
     */
    public static function resolveNetworkUsing(?Closure $resolver): void
    {
        self::$networkResolver = $resolver;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = WebhookEnvelope::fromArray($this->decode($request));

        dispatch(new ProcessWebhookJob($envelope, $this->network($request, $envelope)));

        return response()->json(
            ['status' => 'accepted', 'event_id' => $envelope->eventId],
            202,
        );
    }

    private function network(Request $request, WebhookEnvelope $envelope): ?string
    {
        $network = self::$networkResolver === null
            ? ($envelope->payload['network_id'] ?? null)
            : (self::$networkResolver)($request, $envelope);

        if (! is_string($network) && ! is_int($network)) {
            return null;
        }

        $network = trim((string) $network);

        return $network === '' ? null : $network;
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
