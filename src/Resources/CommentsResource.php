<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\ReactionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\DeleteCommentRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\GetCommentRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\CreateCommentReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\DeleteCommentReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\ListCommentReactionsRequest;
use Saloon\Http\Response;

/**
 * The public comments API: single comments, their reactions, and deletion.
 *
 * Comments are nested beneath their parent post in the Admin REST paths, so
 * lookups and deletes take both the post ID and the comment ID.
 */
final class CommentsResource extends Resource
{
    /**
     * Find a comment by its ID within a post.
     *
     * @throws NotFoundException
     */
    public function findById(int $postId, int $commentId): Comment
    {
        return $this->commentFrom(
            $this->connector()->send(new GetCommentRequest($this->networkId(), $postId, $commentId)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $postId, int $commentId): ?Comment
    {
        try {
            return $this->findById($postId, $commentId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Delete a comment from its post.
     */
    public function delete(int $postId, int $commentId): void
    {
        $this->connector()->send(new DeleteCommentRequest($this->networkId(), $postId, $commentId));
    }

    /**
     * Fetch the first page of reactions on a comment.
     */
    public function reactions(int $commentId, int $perPage = 25): ReactionCollection
    {
        return $this->reactionsFrom(
            $this->connector()->send(new ListCommentReactionsRequest(
                networkId: $this->networkId(),
                commentId: $commentId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every reaction on a comment.
     */
    public function paginateReactions(int $commentId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListCommentReactionsRequest(networkId: $this->networkId(), commentId: $commentId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Add an emoji reaction to a comment.
     */
    public function react(int $commentId, string $emoji): Reaction
    {
        return $this->reactionFrom(
            $this->connector()->send(new CreateCommentReactionRequest($this->networkId(), $commentId, $emoji)),
        );
    }

    /**
     * Remove the caller's reaction from a comment.
     */
    public function removeReaction(int $commentId): void
    {
        $this->connector()->send(new DeleteCommentReactionRequest($this->networkId(), $commentId));
    }

    private function commentFrom(Response $response): Comment
    {
        return $this->ensureComment($response->dto());
    }

    private function reactionsFrom(Response $response): ReactionCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof ReactionCollection) {
            throw new MightyNetworksException('Expected a ReactionCollection from the reactions endpoint.');
        }

        return $dto;
    }

    private function reactionFrom(Response $response): Reaction
    {
        return $this->ensureReaction($response->dto());
    }

    private function ensureComment(mixed $value): Comment
    {
        if (! $value instanceof Comment) {
            throw new MightyNetworksException('Expected a Comment from the comments endpoint.');
        }

        return $value;
    }

    private function ensureReaction(mixed $value): Reaction
    {
        if (! $value instanceof Reaction) {
            throw new MightyNetworksException('Expected a Reaction from the reactions endpoint.');
        }

        return $value;
    }
}
