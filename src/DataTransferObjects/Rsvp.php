<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\RsvpStatus;

/**
 * An RSVP to an event.
 *
 * `event` and `member` are the nested objects the API embeds; `eventInstance`
 * is present only for a specific instance of a recurring event.
 */
final readonly class Rsvp
{
    /**
     * @param  array<int|string, mixed>  $event
     * @param  array<int|string, mixed>  $member
     * @param  array<int|string, mixed>|null  $eventInstance
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $updatedAt,
        public RsvpStatus $status,
        public array $event,
        public array $member,
        public ?array $eventInstance = null,
    ) {}

    /**
     * Create an Rsvp from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            updatedAt: self::date((string) ($data['updated'] ?? '')),
            status: RsvpStatus::tryFrom((string) ($data['status'] ?? '')) ?? RsvpStatus::Yes,
            event: self::arrayOrEmpty($data, 'event'),
            member: self::arrayOrEmpty($data, 'member'),
            eventInstance: self::nullableArray($data, 'event_instance'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private static function arrayOrEmpty(array $data, string $key): array
    {
        return self::nullableArray($data, $key) ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>|null
     */
    private static function nullableArray(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return null;
        }

        $result = [];

        foreach ($value as $itemKey => $item) {
            $result[$itemKey] = $item;
        }

        return $result;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
