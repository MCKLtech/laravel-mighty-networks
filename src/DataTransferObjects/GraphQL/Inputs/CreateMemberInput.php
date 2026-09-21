<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

use MCKLtech\MightyNetworks\Enums\MembershipRole;

/**
 * GraphQL `CreateMemberInput`.
 *
 * Unlike the Admin REST request DTOs, GraphQL input keys are **camelCase**, so
 * {@see CreateMemberInput::toArray()} emits camelCase and omits nulls. `role`
 * takes the GraphQL `MembershipRole` enum spelling (`CONTRIBUTOR`, `HOST`,
 * `MODERATOR`) — these differ in case from the Admin REST {@see MembershipRole}
 * values, so the value is passed through verbatim.
 */
final readonly class CreateMemberInput
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $role = null,
        public ?bool $sendWelcomeEmail = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'role' => $this->role,
            'sendWelcomeEmail' => $this->sendWelcomeEmail,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
