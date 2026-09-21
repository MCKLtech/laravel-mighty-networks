<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Webhooks\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies a Mighty Networks webhook delivery.
 *
 * Mighty Networks webhooks are **not** HMAC-signed. There is no
 * `X-Signature`/`X-Hub-Signature` header and no signature scheme. Instead the
 * configured webhook secret is presented verbatim as a static Bearer token:
 *
 *     Authorization: Bearer YOUR_CONFIGURED_API_KEY
 *
 * Authenticity is therefore a constant-time {@see hash_equals()} comparison of
 * the presented token against `mighty-networks.webhooks.secret`. A timestamp
 * tolerance (config `mighty-networks.webhooks.tolerance`, default 300 seconds)
 * is additionally enforced against `event_timestamp` to limit replay.
 *
 * The secret is never logged and never echoed in the error response.
 */
final class VerifyWebhookSecret
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->verify($request)) {
            abort(403, 'Webhook verification failed.');
        }

        return $next($request);
    }

    private function verify(Request $request): bool
    {
        $secret = config('mighty-networks.webhooks.secret');

        if (! is_string($secret) || $secret === '') {
            // Fail closed: an unconfigured secret can never authenticate.
            return false;
        }

        $presented = $this->bearerToken($request);

        if ($presented === null) {
            return false;
        }

        if (! $this->secretsMatch($presented, $secret)) {
            return false;
        }

        return $this->timestampIsFresh($request);
    }

    /**
     * Extract the token from an `Authorization: Bearer <token>` header. The
     * scheme name is case-insensitive per RFC 7235.
     */
    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header)) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) !== 1) {
            return null;
        }

        $token = trim($matches[1]);

        return $token === '' ? null : $token;
    }

    /**
     * Constant-time comparison. `hash_equals()` does not short-circuit on the
     * first differing byte, so an attacker cannot recover the secret by timing
     * responses.
     */
    private function secretsMatch(string $presented, #[\SensitiveParameter] string $expected): bool
    {
        return hash_equals($expected, $presented);
    }

    private function timestampIsFresh(Request $request): bool
    {
        $decoded = json_decode($request->getContent(), true);

        if (! is_array($decoded)) {
            return false;
        }

        $raw = $decoded['event_timestamp'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return false;
        }

        try {
            $eventTime = CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return false;
        }

        $tolerance = (int) config('mighty-networks.webhooks.tolerance', 300);

        if ($tolerance < 0) {
            $tolerance = 0;
        }

        return abs($eventTime->getTimestamp() - CarbonImmutable::now()->getTimestamp()) <= $tolerance;
    }
}
