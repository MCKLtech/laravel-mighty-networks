<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\OAuth;

use Carbon\CarbonImmutable;
use SensitiveParameter;

/**
 * An OAuth 2.0 token pair issued by a Mighty Network's authorization server.
 *
 * `expiresAt` is the absolute expiry derived from the token endpoint's
 * `expires_in`, so it survives a round trip through the token store.
 */
final readonly class AccessToken
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        #[SensitiveParameter]
        public string $accessToken,
        #[SensitiveParameter]
        public ?string $refreshToken = null,
        public ?CarbonImmutable $expiresAt = null,
        public array $scopes = [],
        public string $tokenType = 'Bearer',
    ) {}

    /**
     * Build a token from a token-endpoint response body.
     *
     * `expires_in` is treated as a number of seconds from now; an explicit
     * `expires_at` (an absolute value persisted by the store) wins when present.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: self::stringOrNull($data, 'refresh_token'),
            expiresAt: self::expiry($data),
            scopes: self::scopes($data),
            tokenType: self::stringOrNull($data, 'token_type') ?? 'Bearer',
        );
    }

    /**
     * A plain-array representation suitable for JSON/cache persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'scopes' => $this->scopes,
            'token_type' => $this->tokenType,
        ];
    }

    /**
     * Whether the access token is expired (or within `$leeway` seconds of it).
     */
    public function isExpired(int $leeway = 60): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt->lessThanOrEqualTo(CarbonImmutable::now()->addSeconds($leeway));
    }

    /**
     * Whether this token carries a refresh token and can be renewed.
     */
    public function isRefreshable(): bool
    {
        return is_string($this->refreshToken) && $this->refreshToken !== '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function expiry(array $data): ?CarbonImmutable
    {
        $absolute = $data['expires_at'] ?? null;

        if (is_string($absolute) && $absolute !== '') {
            return CarbonImmutable::parse($absolute);
        }

        $expiresIn = $data['expires_in'] ?? null;

        return is_numeric($expiresIn)
            ? CarbonImmutable::now()->addSeconds((int) $expiresIn)
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function scopes(array $data): array
    {
        // A persisted token stores an array under `scopes`; a token-endpoint
        // response carries a space-separated `scope` string.
        $scope = $data['scopes'] ?? $data['scope'] ?? null;

        if (is_array($scope)) {
            $scopes = [];

            foreach ($scope as $value) {
                if (is_string($value) && $value !== '') {
                    $scopes[] = $value;
                }
            }

            return $scopes;
        }

        if (! is_string($scope) || trim($scope) === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $scope), static fn (string $value): bool => $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringOrNull(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
