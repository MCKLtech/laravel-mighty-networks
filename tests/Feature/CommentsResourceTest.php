<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\ReactionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\DeleteCommentRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Comments\GetCommentRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\CreateCommentReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\DeleteCommentReactionRequest;
use MCKLtech\MightyNetworks\Requests\Admin\Reactions\ListCommentReactionsRequest;
use MCKLtech\MightyNetworks\Resources\CommentsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class CommentsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function commentPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 55,
            'created_at' => '2024-02-01T09:00:00+00:00',
            'updated_at' => '2024-02-02T09:00:00+00:00',
            'targetable_id' => 7,
            'targetable_type' => 'Post',
            'text' => 'Nice post!',
            'replyable' => true,
            'depth' => 1,
            'cheer_count' => 3,
            'reply_count' => 1,
            'reply_to_id' => null,
            'author_id' => 42,
            'space_id' => 9,
            'files' => [],
            'embedded_link' => null,
            'permalink' => 'https://example.mn.co/posts/7/comments/55',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function reactionPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 99,
            'created_at' => '2024-02-01T09:00:00+00:00',
            'updated_at' => '2024-02-01T09:00:00+00:00',
            'space_id' => 9,
            'network_id' => 12345,
            'targetable_id' => 55,
            'targetable_type' => 'Comment',
            'targetable_space_id' => 9,
            'emoji' => '👍',
            'base_emoji' => '👍',
            'member_id' => 42,
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_comment_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->commentPayload(), 200)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $comment = $resource->findById(7, 55);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertSame(55, $comment->id);
        $this->assertSame('Nice post!', $comment->text);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetCommentRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/comments/55/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Comment not found'], 404)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(7, 55));
    }

    public function test_delete_sends_a_delete_to_the_nested_comment_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $resource->delete(7, 55);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCommentRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/posts/7/comments/55/';
        });
    }

    public function test_reactions_returns_a_typed_collection_for_a_comment(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->reactionPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $reactions = $resource->reactions(55, perPage: 10);

        $this->assertInstanceOf(ReactionCollection::class, $reactions);
        $this->assertCount(1, $reactions);
        $this->assertInstanceOf(Reaction::class, $reactions->first());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCommentReactionsRequest
                && $request->resolveEndpoint() === 'networks/12345/comments/55/reactions'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_react_posts_the_emoji_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->reactionPayload(['emoji' => '🎉', 'base_emoji' => '🎉']), 201)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $reaction = $resource->react(55, '🎉');

        $this->assertSame('🎉', $reaction->emoji);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreateCommentReactionRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/comments/55/reactions'
                && $request->body()->all() === ['emoji' => '🎉'];
        });
    }

    public function test_remove_reaction_sends_a_delete_to_the_reactions_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $resource->removeReaction(55);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeleteCommentReactionRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/comments/55/reactions';
        });
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Comment not found'], 404)]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(7, 55);
    }

    public function test_paginate_reactions_yields_reactions_across_pages(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->reactionPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->reactionPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new CommentsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginateReactions(55, perPage: 10)->items() as $reaction) {
            $this->assertInstanceOf(Reaction::class, $reaction);
            $ids[] = $reaction->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCommentReactionsRequest
                && $request->resolveEndpoint() === 'networks/12345/comments/55/reactions'
                && $request->query()->get('per_page') === 10;
        });
    }
}
