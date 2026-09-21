<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\EventFrequency;

/**
 * An event in a Mighty Networks Network.
 *
 * `creator` is the nested user object the API embeds for the event's author;
 * `images` is a list of absolute image URLs.
 */
final readonly class Event
{
    /**
     * @param  array<int|string, mixed>  $creator
     * @param  array<int, string>  $images
     */
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $postType,
        public array $creator,
        public string $permalink,
        public string $title,
        public string $eventType,
        public array $images = [],
        public ?string $description = null,
        public ?string $recurrenceRule = null,
        public ?bool $rsvpEnabled = null,
        public ?bool $rsvpClosed = null,
        public ?bool $restrictedEvent = null,
        public ?bool $postInFeed = null,
        public ?CarbonImmutable $startsAt = null,
        public ?CarbonImmutable $endsAt = null,
        public ?string $timeZone = null,
        public ?string $location = null,
        public ?string $link = null,
        public ?EventFrequency $frequency = null,
        public ?int $interval = null,
    ) {}

    /**
     * Create an Event from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::date((string) ($data['created_at'] ?? '')),
            updatedAt: self::date((string) ($data['updated_at'] ?? '')),
            postType: (string) ($data['post_type'] ?? ''),
            creator: self::arrayOrEmpty($data, 'creator'),
            permalink: (string) ($data['permalink'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            eventType: (string) ($data['event_type'] ?? ''),
            images: self::stringArray($data, 'images'),
            description: self::stringOrNull($data, 'description'),
            recurrenceRule: self::stringOrNull($data, 'recurrence_rule'),
            rsvpEnabled: self::boolOrNull($data, 'rsvp_enabled'),
            rsvpClosed: self::boolOrNull($data, 'rsvp_closed'),
            restrictedEvent: self::boolOrNull($data, 'restricted_event'),
            postInFeed: self::boolOrNull($data, 'post_in_feed'),
            startsAt: self::dateOrNull($data['starts_at'] ?? null),
            endsAt: self::dateOrNull($data['ends_at'] ?? null),
            timeZone: self::stringOrNull($data, 'time_zone'),
            location: self::stringOrNull($data, 'location'),
            link: self::stringOrNull($data, 'link'),
            frequency: self::frequencyOrNull($data['frequency'] ?? null),
            interval: self::intOrNull($data, 'interval'),
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

    /**
     * @param  array<string, mixed>  $data
     */
    private static function boolOrNull(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function intOrNull(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private static function arrayOrEmpty(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $itemKey => $item) {
            $result[$itemKey] = $item;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private static function stringArray(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private static function frequencyOrNull(mixed $value): ?EventFrequency
    {
        return is_string($value) && $value !== '' ? EventFrequency::tryFrom($value) : null;
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? self::date($value) : null;
    }

    private static function date(string $value): CarbonImmutable
    {
        return $value === ''
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($value);
    }
}
