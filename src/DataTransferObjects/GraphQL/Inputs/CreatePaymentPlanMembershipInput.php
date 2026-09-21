<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `CreatePaymentPlanMembershipInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreatePaymentPlanMembershipInput
{
    public function __construct(
        public string $memberId,
        public string $planId,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'memberId' => $this->memberId,
            'planId' => $this->planId,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
