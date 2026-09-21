<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * A file or media asset uploaded to a Network.
 */
final readonly class Asset
{
    public function __construct(
        public int $id,
        public string $type,
        public ?string $url = null,
        public ?string $name = null,
    ) {}

    /**
     * Create an Asset from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            type: (string) ($data['type'] ?? ''),
            url: self::stringOrNull($data, 'url'),
            name: self::stringOrNull($data, 'name'),
        );
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
