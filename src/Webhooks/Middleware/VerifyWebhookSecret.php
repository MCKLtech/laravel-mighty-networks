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
 * the presented token against `mighty-networks.webhooks.secret`, or against
 * the secret returned by {@see self::resolveSecretUsing()} when an application
 * keeps one secret per network.
 *
 * `event_timestamp` is not checked unless `mighty-networks.webhooks.tolerance`
 * is set. The body is not signed, so whoever holds the secret can send any
 * timestamp, and a tolerance would refuse retries that arrive hours later if
 * they keep their original timestamp.
 *
 * The secret is never logged and never echoed in the error response.
 */
final class VerifyWebhookSecret
{
    /**
     * @var (Closure(Request): (string|null))|null
     */
    private static ?Closure $secretResolver = null;

    /**
     * Choose the expected secret for each request, for example the secret of
     * the network named in the route. Returning null or an empty string refuses
     * the delivery. While a resolver is set, `mighty-networks.webhooks.secret`
     * is not read. Pass null to go back to the configured secret.
     *
     * @param  (Closure(Request): (string|null))|null  $resolver
     */
    public static function resolveSecretUsing(?Closure $resolver): void
    {
        self::$secretResolver = $resolver;
    }

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
        $secret = self::$secretResolver === null
            ? config('mighty-networks.webhooks.secret')
            : (self::$secretResolver)($request);

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

        $body = json_decode($request->getContent(), true);

        // A body that is not a JSON object is refused whatever the tolerance.
        return is_array($body) && $this->timestampIsFresh($body);
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

    /**
     * @param  array<array-key, mixed>  $body
     */
    private function timestampIsFresh(array $body): bool
    {
        $tolerance = config('mighty-networks.webhooks.tolerance');

        if (! is_numeric($tolerance)) {
            // No tolerance configured: the timestamp is not checked.
            return true;
        }

        $tolerance = max(0, (int) $tolerance);

        $raw = $body['event_timestamp'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return false;
        }

        try {
            $eventTime = CarbonImmutable::parse($raw);
        } catch (\Throwable) {
            return false;
        }

        return abs($eventTime->getTimestamp() - CarbonImmutable::now()->getTimestamp()) <= $tolerance;
    }
}
