<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\DataTransferObjects\Asset;
use MCKLtech\MightyNetworks\Enums\AssetStyle;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Requests\Admin\Assets\CreateAssetRequest;
use MCKLtech\MightyNetworks\Resources\AssetsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class AssetsResourceTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->temporaryFiles = [];

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(): array
    {
        return [
            'id' => 21,
            'url' => 'https://cdn.mn.co/assets/21.png',
            'type' => 'StaticAsset',
            'name' => 'logo.png',
        ];
    }

    private function part(CreateAssetRequest $request, string $name): ?MultipartValue
    {
        foreach ($request->body()->all() as $part) {
            if ($part->name === $name) {
                return $part;
            }
        }

        return null;
    }

    public function test_upload_reads_a_local_file_and_sends_the_optional_fields(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mn_asset_');
        $this->assertIsString($file);
        file_put_contents($file, 'file-contents');
        $this->temporaryFiles[] = $file;

        $mock = new MockClient([MockResponse::make($this->assetPayload(), 201)]);

        $resource = new AssetsResource($this->admin($mock), '12345');

        $asset = $resource->upload(
            path: $file,
            assetStyle: AssetStyle::Avatar,
            metadata: ['is_main_image' => true, 'source' => 'test'],
            originalAspectRatio: '1:1',
        );

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame(21, $asset->id);

        $mock->assertSent(function ($request) use ($file): bool {
            if (! $request instanceof CreateAssetRequest) {
                return false;
            }

            $filePart = $this->part($request, 'asset_file');
            $stylePart = $this->part($request, 'asset_style');
            $ratioPart = $this->part($request, 'original_aspect_ratio');
            $metadataPart = $this->part($request, 'metadata[is_main_image]');

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/assets'
                && $filePart !== null
                && $filePart->value === 'file-contents'
                && $filePart->filename === basename($file)
                && $stylePart !== null
                && $stylePart->value === 'avatar'
                && $ratioPart !== null
                && $ratioPart->value === '1:1'
                && $metadataPart !== null
                && $metadataPart->value === 'true'
                && $this->part($request, 'metadata[source]')?->value === 'test';
        });
    }

    public function test_upload_base64_decodes_and_carries_the_filename_and_content_type(): void
    {
        $mock = new MockClient([MockResponse::make($this->assetPayload(), 201)]);

        $resource = new AssetsResource($this->admin($mock), '12345');

        $resource->uploadBase64(
            base64: base64_encode('binary-data'),
            filename: 'logo.png',
            contentType: 'image/png',
        );

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateAssetRequest) {
                return false;
            }

            $filePart = $this->part($request, 'asset_file');

            return $filePart !== null
                && $filePart->value === 'binary-data'
                && $filePart->filename === 'logo.png'
                && ($filePart->headers['Content-Type'] ?? null) === 'image/png';
        });
    }

    public function test_upload_from_url_sends_the_source_url(): void
    {
        $mock = new MockClient([MockResponse::make($this->assetPayload(), 201)]);

        $resource = new AssetsResource($this->admin($mock), '12345');

        $resource->uploadFromUrl('https://example.com/logo.png');

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateAssetRequest) {
                return false;
            }

            return $this->part($request, 'source_url')?->value === 'https://example.com/logo.png';
        });
    }

    public function test_upload_base64_rejects_invalid_base64(): void
    {
        $resource = new AssetsResource($this->admin(new MockClient([])), '12345');

        $this->expectException(MightyNetworksException::class);

        $resource->uploadBase64('not valid base64 !!!', 'file.bin');
    }

    public function test_contents_over_the_25_mb_cap_are_rejected_client_side(): void
    {
        $resource = new AssetsResource($this->admin(new MockClient([])), '12345');

        $this->expectException(MightyNetworksException::class);
        $this->expectExceptionMessage('exceeds the 25 MB upload limit');

        $resource->uploadContents(str_repeat('a', AssetsResource::MAX_BYTES + 1), 'big.bin');
    }

    public function test_a_413_is_surfaced_as_a_clear_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Payload too large'], 413)]);

        $resource = new AssetsResource($this->admin($mock), '12345');

        $this->expectException(MightyNetworksException::class);
        $this->expectExceptionMessage('25 MB');

        $resource->uploadFromUrl('https://example.com/huge.mp4');
    }

    public function test_a_402_is_surfaced_as_a_clear_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Payment required'], 402)]);

        $resource = new AssetsResource($this->admin($mock), '12345');

        $this->expectException(MightyNetworksException::class);
        $this->expectExceptionMessage('current plan');

        $resource->uploadFromUrl('https://example.com/logo.png');
    }

    public function test_an_unreadable_file_raises_an_exception(): void
    {
        $resource = new AssetsResource($this->admin(new MockClient([])), '12345');

        $this->expectException(MightyNetworksException::class);
        $this->expectExceptionMessage('is not readable');

        $resource->upload('/tmp/mighty-networks-missing-file.png');
    }
}
