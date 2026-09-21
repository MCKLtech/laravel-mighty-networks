<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Enums\WebhookEventType;

/**
 * A GraphQL `WebhookCallback` as returned by the webhook callback mutations.
 */
final readonly class GraphQLWebhookCallback
{
    /**
     * @param  list<WebhookEventType>  $includedEvents
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $resourceId,
        public string $url,
        public ?string $apiKey = null,
        public array $includedEvents = [],
        public bool $disabled = false,
        public ?CarbonImmutable $disabledAt = null,
        public int $consecutiveFailures = 0,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $events = [];

        foreach (is_array($data['includedEvents'] ?? null) ? $data['includedEvents'] : [] as $event) {
            if (is_string($event)) {
                $resolved = WebhookEventType::fromEventName($event);

                if ($resolved !== null) {
                    $events[] = $resolved;
                }
            }
        }

        return new self(
            id: (string) ($data['id'] ?? ''),
            resourceId: (string) ($data['resourceId'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            apiKey: self::stringOrNull($data, 'apiKey'),
            includedEvents: $events,
            disabled: (bool) ($data['disabled'] ?? false),
            disabledAt: self::dateOrNull($data['disabledAt'] ?? null),
            consecutiveFailures: (int) ($data['consecutiveFailures'] ?? 0),
            createdAt: self::dateOrNull($data['createdAt'] ?? null),
            updatedAt: self::dateOrNull($data['updatedAt'] ?? null),
            raw: $data,
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

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
