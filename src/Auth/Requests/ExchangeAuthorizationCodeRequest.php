<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Auth\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use SensitiveParameter;

/**
 * `POST {community}/oauth/token` with `grant_type=authorization_code`.
 *
 * Confidential clients send a `client_secret`; public clients send only the
 * PKCE `code_verifier`.
 */
final class ExchangeAuthorizationCodeRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        #[SensitiveParameter]
        private readonly string $clientId,
        #[SensitiveParameter]
        private readonly ?string $clientSecret,
        private readonly ?string $redirectUri,
        #[SensitiveParameter]
        private readonly string $code,
        #[SensitiveParameter]
        private readonly string $codeVerifier,
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
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'client_id' => $this->clientId,
            'code_verifier' => $this->codeVerifier,
        ];

        if ($this->clientSecret !== null && $this->clientSecret !== '') {
            $body['client_secret'] = $this->clientSecret;
        }

        if ($this->redirectUri !== null && $this->redirectUri !== '') {
            $body['redirect_uri'] = $this->redirectUri;
        }

        return $body;
    }
}
