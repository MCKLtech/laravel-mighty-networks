<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Support;

/**
 * Proof Key for Code Exchange (RFC 7636) helpers.
 *
 * The verifier is a high-entropy random string; the challenge is its
 * SHA-256 digest, base64url-encoded without padding.
 */
final class Pkce
{
    /**
     * Generate a random `code_verifier` (43–128 unreserved characters).
     */
    public static function verifier(int $bytes = 48): string
    {
        $length = max(32, $bytes);

        return self::base64Url(random_bytes($length));
    }

    /**
     * Derive the S256 `code_challenge` from a verifier.
     */
    public static function challenge(string $verifier): string
    {
        return self::base64Url(hash('sha256', $verifier, true));
    }

    /**
     * Base64url-encode without padding (RFC 4648 §5).
     */
    public static function base64Url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
