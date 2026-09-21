<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\DataTransferObjects\Asset;
use MCKLtech\MightyNetworks\Enums\AssetStyle;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\RequestException;
use MCKLtech\MightyNetworks\Requests\Admin\Assets\CreateAssetRequest;
use Saloon\Data\MultipartValue;
use Saloon\Http\Response;

/**
 * The public assets API.
 *
 * Asset uploads are `multipart/form-data`, never JSON, and are exposed in the
 * three documented forms: a local file ({@see upload()}), raw base64 contents
 * with a filename/content type ({@see uploadBase64()}), or a remote
 * `source_url` for the API to fetch ({@see uploadFromUrl()}).
 *
 * The Admin API caps a single asset at 25 MB. That cap is enforced client-side
 * for local and base64 uploads, and a clear {@see MightyNetworksException} is
 * raised if the API rejects an upload with HTTP 413 (too large) or 402
 * (the Network's plan does not include the upload).
 *
 * Note: video and audio recordings are **not** supported here. Those must be
 * uploaded through the GraphQL `createUploadSession` flow instead.
 */
final class AssetsResource extends Resource
{
    /**
     * The documented maximum size of a single uploaded asset, in bytes.
     */
    public const MAX_BYTES = 25 * 1024 * 1024;

    /**
     * Upload a local file as an asset.
     *
     * @param  array<string, string|int|float|bool>|null  $metadata
     */
    public function upload(
        string $path,
        AssetStyle|string|null $assetStyle = null,
        ?array $metadata = null,
        ?string $originalAspectRatio = null,
    ): Asset {
        if (! is_file($path) || ! is_readable($path)) {
            throw new MightyNetworksException(sprintf('Asset file [%s] is not readable.', $path));
        }

        $size = filesize($path);
        $this->guardSize(is_int($size) ? $size : 0);

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new MightyNetworksException(sprintf('Unable to read asset file [%s].', $path));
        }

        return $this->uploadContents(
            contents: $contents,
            filename: basename($path),
            assetStyle: $assetStyle,
            metadata: $metadata,
            originalAspectRatio: $originalAspectRatio,
        );
    }

    /**
     * Upload raw binary contents as an asset, tagged with a filename and
     * optional content type.
     *
     * @param  array<string, string|int|float|bool>|null  $metadata
     */
    public function uploadContents(
        string $contents,
        string $filename,
        ?string $contentType = null,
        AssetStyle|string|null $assetStyle = null,
        ?array $metadata = null,
        ?string $originalAspectRatio = null,
    ): Asset {
        $this->guardSize(strlen($contents));

        $headers = $contentType === null ? [] : ['Content-Type' => $contentType];

        return $this->send($this->withOptions(
            [new MultipartValue('asset_file', $contents, $filename, $headers)],
            $assetStyle,
            $metadata,
            $originalAspectRatio,
        ));
    }

    /**
     * Upload base64-encoded contents as an asset.
     *
     * @param  array<string, string|int|float|bool>|null  $metadata
     */
    public function uploadBase64(
        string $base64,
        string $filename,
        ?string $contentType = null,
        AssetStyle|string|null $assetStyle = null,
        ?array $metadata = null,
        ?string $originalAspectRatio = null,
    ): Asset {
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new MightyNetworksException('The supplied asset contents are not valid base64.');
        }

        return $this->uploadContents(
            contents: $decoded,
            filename: $filename,
            contentType: $contentType,
            assetStyle: $assetStyle,
            metadata: $metadata,
            originalAspectRatio: $originalAspectRatio,
        );
    }

    /**
     * Ask the API to download and store an asset from a remote URL.
     *
     * @param  array<string, string|int|float|bool>|null  $metadata
     */
    public function uploadFromUrl(
        string $sourceUrl,
        AssetStyle|string|null $assetStyle = null,
        ?array $metadata = null,
        ?string $originalAspectRatio = null,
    ): Asset {
        return $this->send($this->withOptions(
            [new MultipartValue('source_url', $sourceUrl)],
            $assetStyle,
            $metadata,
            $originalAspectRatio,
        ));
    }

    /**
     * Append the optional multipart fields shared by every upload variant.
     *
     * @param  array<int, MultipartValue>  $parts
     * @param  array<string, string|int|float|bool>|null  $metadata
     * @return array<int, MultipartValue>
     */
    private function withOptions(
        array $parts,
        AssetStyle|string|null $assetStyle,
        ?array $metadata,
        ?string $originalAspectRatio,
    ): array {
        if ($assetStyle !== null) {
            $parts[] = new MultipartValue(
                'asset_style',
                $assetStyle instanceof AssetStyle ? $assetStyle->value : $assetStyle,
            );
        }

        if ($originalAspectRatio !== null) {
            $parts[] = new MultipartValue('original_aspect_ratio', $originalAspectRatio);
        }

        foreach ($metadata ?? [] as $key => $value) {
            $parts[] = new MultipartValue(sprintf('metadata[%s]', $key), $this->metadataValue($value));
        }

        return $parts;
    }

    private function metadataValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * @param  array<int, MultipartValue>  $parts
     */
    private function send(array $parts): Asset
    {
        try {
            $response = $this->connector()->send(new CreateAssetRequest($this->networkId(), $parts));
        } catch (RequestException $exception) {
            if ($exception->getStatus() === 413 || $exception->getStatus() === 402) {
                throw $this->mapUploadFailure($exception);
            }

            throw $exception;
        }

        return $this->assetFrom($response);
    }

    /**
     * Translate the two documented upload-failure statuses into clear errors.
     */
    private function mapUploadFailure(RequestException $exception): MightyNetworksException
    {
        $status = $exception->getStatus();

        if ($status === 413) {
            return new MightyNetworksException(
                'The asset exceeds the 25 MB upload limit.',
                $status,
                $exception,
            );
        }

        return new MightyNetworksException(
            "Asset uploads are not available on the Network's current plan.",
            $status,
            $exception,
        );
    }

    private function guardSize(int $bytes): void
    {
        if ($bytes > self::MAX_BYTES) {
            throw new MightyNetworksException(sprintf(
                'The asset is %d bytes, which exceeds the 25 MB upload limit.',
                $bytes,
            ));
        }
    }

    private function assetFrom(Response $response): Asset
    {
        $dto = $response->dto();

        if (! $dto instanceof Asset) {
            throw new MightyNetworksException('Expected an Asset from the assets endpoint.');
        }

        return $dto;
    }
}
