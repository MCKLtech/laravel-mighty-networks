<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `UpdateMemberInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class UpdateMemberInput
{
    public function __construct(
        public string $id,
        public ?string $avatarId = null,
        public ?string $email = null,
        public ?string $emailVerificationCode = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $shortBio = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'avatarId' => $this->avatarId,
            'email' => $this->email,
            'emailVerificationCode' => $this->emailVerificationCode,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'shortBio' => $this->shortBio,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
