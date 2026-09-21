<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * An invitation to join a Network or a plan.
 *
 * `userId` is deprecated by the API in favour of `senderId`, but is retained
 * because the response still documents it.
 */
final readonly class Invite
{
    public function __construct(
        public int $id,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public string $recipientEmail,
        public string $recipientFirstName,
        public string $recipientLastName,
        public int $senderId,
        public ?int $userId = null,
    ) {}

    /**
     * Create an Invite from a decoded Admin API payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            createdAt: self::dateOrNow($data['created_at'] ?? null),
            updatedAt: self::dateOrNow($data['updated_at'] ?? null),
            recipientEmail: (string) ($data['recipient_email'] ?? ''),
            recipientFirstName: (string) ($data['recipient_first_name'] ?? ''),
            recipientLastName: (string) ($data['recipient_last_name'] ?? ''),
            senderId: (int) ($data['sender_id'] ?? 0),
            userId: is_numeric($data['user_id'] ?? null) ? (int) $data['user_id'] : null,
        );
    }

    private static function dateOrNow(mixed $value): CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : CarbonImmutable::now();
    }
}
