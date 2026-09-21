<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects;

use MCKLtech\MightyNetworks\Enums\MembershipRole;

/**
 * Request payload for updating a member's role and profile information.
 */
final readonly class UpdateMemberData
{
    public function __construct(
        public ?string $email = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public MembershipRole|string|null $role = null,
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
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
