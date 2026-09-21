<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs;

/**
 * GraphQL `CreateCommentInput`. Emits camelCase keys, omitting nulls.
 */
final readonly class CreateCommentInput
{
    public function __construct(
        public string $body,
        public string $postId,
        public ?string $replyToId = null,
        public ?string $clientMutationId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'body' => $this->body,
            'postId' => $this->postId,
            'replyToId' => $this->replyToId,
            'clientMutationId' => $this->clientMutationId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
