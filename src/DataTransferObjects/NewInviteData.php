<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

/**
 * Request payload for creating a Network invite.
 */
final readonly class NewInviteData
{
    public function __construct(
        public string $recipientEmail,
        public ?string $recipientFirstName = null,
        public ?string $recipientLastName = null,
        public ?int $userId = null,
    ) {}

    /**
     * Convert to the snake_case API payload, omitting null values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'recipient_email' => $this->recipientEmail,
                'recipient_first_name' => $this->recipientFirstName,
                'recipient_last_name' => $this->recipientLastName,
                'user_id' => $this->userId,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
