<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Assets;

use MCKLtech\MightyNetworks\DataTransferObjects\Asset;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use MCKLtech\MightyNetworks\Resources\AssetsResource;
use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasMultipartBody;

/**
 * `POST networks/{network_id}/assets`
 *
 * Assets are uploaded as `multipart/form-data`, never JSON. Build the parts
 * with {@see MultipartValue} in {@see AssetsResource}.
 */
final class CreateAssetRequest extends AdminRequest implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, MultipartValue>  $parts
     */
    public function __construct(
        int|string $networkId,
        protected readonly array $parts,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('assets');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int, MultipartValue>
     */
    protected function defaultBody(): array
    {
        return $this->parts;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Asset
    {
        $data = $response->json();

        return Asset::fromArray(is_array($data) ? $data : []);
    }
}
