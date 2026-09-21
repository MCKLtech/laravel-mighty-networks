<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberType;

/**
 * Request payload for creating a member.
 */
final readonly class NewMemberData
{
    /**
     * @param  array<int, int>|null  $spaceIds
     */
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public MembershipRole|string|null $role = null,
        public ?MemberType $memberType = null,
        public ?array $spaceIds = null,
        public bool $sendWelcomeEmail = true,
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
                'email' => $this->email,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'role' => $this->role instanceof MembershipRole ? $this->role->value : $this->role,
                'member_type' => $this->memberType?->value,
                'space_ids' => $this->spaceIds,
                'send_welcome_email' => $this->sendWelcomeEmail,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
