<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

use MCKLtech\MightyNetworks\Enums\WebhookEventType;

/**
 * GraphQL `UpdateWebhookCallbackInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class UpdateWebhookCallbackInput
{
    /**
     * @param  list<WebhookEventType|string>|null  $includedEvents
     */
    public function __construct(
        public string $id,
        public ?string $url = null,
        public ?string $apiKey = null,
        public ?array $includedEvents = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $events = null;

        if ($this->includedEvents !== null) {
            $events = array_map(
                static fn (WebhookEventType|string $event): string => $event instanceof WebhookEventType ? $event->value : $event,
                $this->includedEvents,
            );
        }

        return array_filter([
            'id' => $this->id,
            'url' => $this->url,
            'apiKey' => $this->apiKey,
            'includedEvents' => $events,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
