<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Resources;

use MCKLtech\MightyNetworks\Collections\CommentCollection;
use MCKLtech\MightyNetworks\Collections\PostCollection;
use MCKLtech\MightyNetworks\Collections\ReactionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCommentData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPostData;
use MCKLtech\MightyNetworks\DataTransferObjects\Post;
use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePostData;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\CreateCommentRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\ListCommentsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\CreatePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\DeletePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\GetPostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\ListPostsRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\MutePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\ReplacePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\UnmutePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Posts\UpdatePostRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\CreatePostReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\DeletePostReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\ListPostReactionsRequest;
use Saloon\Http\Response;

/**
 * The public posts API: posts, their nested comments and reactions, and
 * per-user mute state.
 */
final class PostsResource extends Resource
{
    /**
     * Fetch the first page of posts, optionally scoped to one space.
     */
    public function all(?int $spaceId = null, int $perPage = 25): PostCollection
    {
        return $this->postCollectionFrom(
            $this->connector()->send(new ListPostsRequest(
                networkId: $this->networkId(),
                spaceId: $spaceId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every post, optionally scoped to one space.
     */
    public function paginate(?int $spaceId = null, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPostsRequest(networkId: $this->networkId(), spaceId: $spaceId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Run a callback for every post, fetching pages lazily.
     *
     * @param  callable(Post): void  $callback
     */
    public function each(callable $callback, ?int $spaceId = null, int $perPage = 25): void
    {
        foreach ($this->paginate(spaceId: $spaceId, perPage: $perPage)->items() as $item) {
            $callback($this->ensurePost($item));
        }
    }

    /**
     * Find a post by its numeric ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): Post
    {
        return $this->postFrom(
            $this->connector()->send(new GetPostRequest($this->networkId(), $id)),
        );
    }

    /**
     * Like {@see findById()} but returns null instead of throwing a 404.
     */
    public function findByIdOrNull(int $id): ?Post
    {
        try {
            return $this->findById($id);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Create a new post, optionally notifying the Network.
     */
    public function create(NewPostData $data, ?bool $notify = null): Post
    {
        return $this->postFrom(
            $this->connector()->send(new CreatePostRequest($this->networkId(), $data, $notify)),
        );
    }

    /**
     * Partially update a post (HTTP PATCH), optionally notifying the Network.
     */
    public function update(int $id, UpdatePostData $data, ?bool $notify = null): Post
    {
        return $this->postFrom(
            $this->connector()->send(new UpdatePostRequest($this->networkId(), $id, $data, $notify)),
        );
    }

    /**
     * Fully replace a post (HTTP PUT), optionally notifying the Network.
     */
    public function replace(int $id, UpdatePostData $data, ?bool $notify = null): Post
    {
        return $this->postFrom(
            $this->connector()->send(new ReplacePostRequest($this->networkId(), $id, $data, $notify)),
        );
    }

    /**
     * Permanently delete a post.
     */
    public function delete(int $id): void
    {
        $this->connector()->send(new DeletePostRequest($this->networkId(), $id));
    }

    /**
     * Mute a post for a user (unfollow notifications).
     */
    public function mute(int $postId, int $userId): void
    {
        $this->connector()->send(new MutePostRequest($this->networkId(), $postId, $userId));
    }

    /**
     * Unmute a post for a user (refollow notifications).
     */
    public function unmute(int $postId, int $userId): void
    {
        $this->connector()->send(new UnmutePostRequest($this->networkId(), $postId, $userId));
    }

    /**
     * Fetch the first page of comments on a post.
     */
    public function comments(int $postId, int $perPage = 25): CommentCollection
    {
        return $this->commentCollectionFrom(
            $this->connector()->send(new ListCommentsRequest(
                networkId: $this->networkId(),
                postId: $postId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every comment on a post.
     */
    public function paginateComments(int $postId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListCommentsRequest(networkId: $this->networkId(), postId: $postId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Create a comment (or reply) on a post.
     */
    public function createComment(int $postId, NewCommentData $data): Comment
    {
        return $this->commentFrom(
            $this->connector()->send(new CreateCommentRequest($this->networkId(), $postId, $data)),
        );
    }

    /**
     * Fetch the first page of reactions on a post.
     */
    public function reactions(int $postId, int $perPage = 25): ReactionCollection
    {
        return $this->reactionsFrom(
            $this->connector()->send(new ListPostReactionsRequest(
                networkId: $this->networkId(),
                postId: $postId,
                perPage: $perPage,
            )),
        );
    }

    /**
     * Lazily paginate through every reaction on a post.
     */
    public function paginateReactions(int $postId, int $perPage = 25): AdminPagedPaginator
    {
        return $this->connector()
            ->paginate(new ListPostReactionsRequest(networkId: $this->networkId(), postId: $postId))
            ->setPerPageLimit($perPage);
    }

    /**
     * Add an emoji reaction to a post.
     */
    public function react(int $postId, string $emoji): Reaction
    {
        return $this->reactionFrom(
            $this->connector()->send(new CreatePostReactionRequest($this->networkId(), $postId, $emoji)),
        );
    }

    /**
     * Remove the caller's reaction from a post.
     */
    public function removeReaction(int $postId): void
    {
        $this->connector()->send(new DeletePostReactionRequest($this->networkId(), $postId));
    }

    private function postFrom(Response $response): Post
    {
        return $this->ensurePost($response->dto());
    }

    private function postCollectionFrom(Response $response): PostCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof PostCollection) {
            throw new MightyNetworksException('Expected a PostCollection from the posts endpoint.');
        }

        return $dto;
    }

    private function commentFrom(Response $response): Comment
    {
        return $this->ensureComment($response->dto());
    }

    private function commentCollectionFrom(Response $response): CommentCollection
    {
        $dto = $response->dto();

        if (! $dto instanceof CommentCollection) {
            throw new MightyNetworksException('Expected a CommentCollection from the comments endpoint.');
        }

        return $dto;
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

    private function ensurePost(mixed $value): Post
    {
        if (! $value instanceof Post) {
            throw new MightyNetworksException('Expected a Post from the posts endpoint.');
        }

        return $value;
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
