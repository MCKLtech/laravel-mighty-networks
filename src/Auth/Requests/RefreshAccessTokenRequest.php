<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Auth\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use SensitiveParameter;

/**
 * `POST {community}/oauth/token` with `grant_type=refresh_token`.
 *
 * Refresh tokens rotate, so callers must persist the newly returned value.
 */
final class RefreshAccessTokenRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        #[SensitiveParameter]
        private readonly string $clientId,
        #[SensitiveParameter]
        private readonly ?string $clientSecret,
        #[SensitiveParameter]
        private readonly string $refreshToken,
    ) {}

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return 'oauth/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        $body = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
        ];

        if ($this->clientSecret !== null && $this->clientSecret !== '') {
            $body['client_secret'] = $this->clientSecret;
        }

        return $body;
    }
}
