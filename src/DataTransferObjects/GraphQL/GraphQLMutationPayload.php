<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\DataTransferObjects\GraphQL;

/**
 * The decoded payload of a GraphQL mutation.
 *
 * Every Mighty mutation payload carries the same envelope — `clientMutationId`,
 * a (usually empty) `errors` list and the changed entity/entities. Entities
 * that have a dedicated GraphQL DTO expose a typed accessor
 * ({@see GraphQLMutationPayload::member()}, {@see GraphQLMutationPayload::webhookCallback()},
 * {@see GraphQLMutationPayload::rsvp()}, {@see GraphQLMutationPayload::reaction()},
 * {@see GraphQLMutationPayload::spacesCollection()}); the remaining entities
 * (post, comment, space, event, payment plan, …) are available as raw decoded
 * arrays through {@see GraphQLMutationPayload::entity()} and {@see GraphQLMutationPayload::$data}.
 */
final readonly class GraphQLMutationPayload
{
    /**
     * @param  list<string>  $errors
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public ?string $clientMutationId = null,
        public array $errors = [],
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $clientMutationId = $data['clientMutationId'] ?? null;
        $errors = [];

        foreach (is_array($data['errors'] ?? null) ? $data['errors'] : [] as $error) {
            if (is_string($error)) {
                $errors[] = $error;
            }
        }

        return new self(
            clientMutationId: is_string($clientMutationId) && $clientMutationId !== '' ? $clientMutationId : null,
            errors: $errors,
            data: $data,
        );
    }

    /**
     * Whether the mutation reported no application-level errors.
     *
     * GraphQL transport failures never reach this DTO — the connector raises
     * them — so this reflects the mutation's own `errors` list only.
     */
    public function succeeded(): bool
    {
        return $this->errors === [];
    }

    /**
     * The first reported error, if any.
     */
    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * A nested entity object by key (e.g. `post`, `space`, `event`).
     *
     * @return array<string, mixed>|null
     */
    public function entity(string $key): ?array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    /**
     * The `deletedId` returned by delete mutations.
     */
    public function deletedId(): ?string
    {
        $value = $this->data['deletedId'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The count returned by bulk mutations such as `createInvites`.
     */
    public function count(): ?int
    {
        $value = $this->data['count'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * The `mode` returned by `createInvites`.
     */
    public function mode(): ?string
    {
        $value = $this->data['mode'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The `outcome` returned by membership mutations.
     */
    public function outcome(): ?string
    {
        $value = $this->data['outcome'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The changed member, when the payload carries one.
     */
    public function member(): ?GraphQLMember
    {
        $member = $this->entity('member');

        return $member === null ? null : GraphQLMember::fromArray($member);
    }

    /**
     * The created/updated webhook callback, when present.
     */
    public function webhookCallback(): ?GraphQLWebhookCallback
    {
        $callback = $this->entity('webhookCallback');

        return $callback === null ? null : GraphQLWebhookCallback::fromArray($callback);
    }

    /**
     * The created RSVP, when present.
     */
    public function rsvp(): ?GraphQLRsvp
    {
        $rsvp = $this->entity('rsvp');

        return $rsvp === null ? null : GraphQLRsvp::fromArray($rsvp);
    }

    /**
     * The created reaction, when present.
     */
    public function reaction(): ?GraphQLReaction
    {
        $reaction = $this->entity('reaction');

        return $reaction === null ? null : GraphQLReaction::fromArray($reaction);
    }

    /**
     * The created space collection, when present.
     */
    public function spacesCollection(): ?GraphQLSpacesCollection
    {
        $collection = $this->entity('collection');

        return $collection === null ? null : GraphQLSpacesCollection::fromArray($collection);
    }
}
