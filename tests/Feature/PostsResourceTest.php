<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\CommentCollection;
use MCKLtech\MightyNetworks\Collections\PostCollection;
use MCKLtech\MightyNetworks\Collections\ReactionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCommentData;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPostData;
use MCKLtech\MightyNetworks\DataTransferObjects\Post;
use MCKLtech\MightyNetworks\DataTransferObjects\Reaction;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePostData;
use MCKLtech\MightyNetworks\Enums\PostStatus;
use MCKLtech\MightyNetworks\Enums\PostType;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
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
use MCKLtech\MightyNetworks\Resources\PostsResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class PostsResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'creator_id' => 42,
            'space_id' => 9,
            'summary' => 'A short summary',
            'description' => 'The full description',
            'post_type' => 'article',
            'images' => ['https://cdn.mn.co/posts/7.jpg'],
            'title' => 'Hello community',
            'status' => 'posted',
            'published_at' => '2024-01-15T10:30:00+00:00',
            'last_activity_at' => '2024-04-01T08:00:00+00:00',
            'content_type' => 'article',
            'comments_enabled' => true,
            'permalink' => 'https://example.mn.co/posts/7',
        ], $overrides);
    }

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
            'targetable_id' => 7,
            'targetable_type' => 'Post',
            'targetable_space_id' => 9,
            'emoji' => '👍',
            'base_emoji' => '👍',
            'member_id' => 42,
        ], $overrides);
    }

    public function test_find_by_id_returns_a_typed_post_and_asserts_the_request(): void
    {
        $mock = new MockClient([MockResponse::make($this->postPayload(), 200)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $post = $resource->findById(7);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame(7, $post->id);
        $this->assertSame(PostType::Article, $post->postType);
        $this->assertSame(PostStatus::Posted, $post->status);

        $mock->assertSent(function ($request): bool {
            return $request instanceof GetPostRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/'
                && $request->getMethod() === Method::GET;
        });
    }

    public function test_find_by_id_or_null_returns_null_on_a_404(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Post not found'], 404)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $this->assertNull($resource->findByIdOrNull(7));
    }

    public function test_find_by_id_or_null_returns_a_post_on_success(): void
    {
        $mock = new MockClient([MockResponse::make($this->postPayload(), 200)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $this->assertInstanceOf(Post::class, $resource->findByIdOrNull(7));
    }

    public function test_all_returns_a_post_collection_and_applies_the_space_filter(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->postPayload(['id' => 1]), $this->postPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...', 'next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $posts = $resource->all(spaceId: 9, perPage: 50);

        $this->assertInstanceOf(PostCollection::class, $posts);
        $this->assertCount(2, $posts);
        $this->assertSame([1, 2], $posts->map(static fn (Post $post): int => $post->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPostsRequest
                && $request->resolveEndpoint() === 'networks/12345/posts'
                && $request->query()->get('space_id') === 9
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_create_maps_the_dto_and_sends_the_notify_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make($this->postPayload(['id' => 99]), 201)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $post = $resource->create(
            new NewPostData(spaceId: 9, title: 'Hello', description: 'Body', postType: PostType::Article),
            notify: true,
        );

        $this->assertSame(99, $post->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreatePostRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/posts'
                && $request->query()->get('notify') === true
                && $request->body()->all() === [
                    'space_id' => 9,
                    'title' => 'Hello',
                    'description' => 'Body',
                    'post_type' => 'article',
                ];
        });
    }

    public function test_update_sends_a_patch_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->postPayload(), 200)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->update(7, new UpdatePostData(title: 'Updated'));

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof UpdatePostRequest) {
                return false;
            }

            return $request->getMethod() === Method::PATCH
                && $request->resolveEndpoint() === 'networks/12345/posts/7/'
                && $request->body()->all() === ['title' => 'Updated']
                && $request->query()->get('notify') === null;
        });
    }

    public function test_replace_sends_a_put_with_the_changed_fields(): void
    {
        $mock = new MockClient([MockResponse::make($this->postPayload(), 200)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->replace(7, new UpdatePostData(title: 'Replaced'), notify: false);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof ReplacePostRequest) {
                return false;
            }

            return $request->getMethod() === Method::PUT
                && $request->resolveEndpoint() === 'networks/12345/posts/7/'
                && $request->body()->all() === ['title' => 'Replaced']
                && $request->query()->get('notify') === false;
        });
    }

    public function test_delete_sends_a_delete_to_the_post_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->delete(7);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePostRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/posts/7/';
        });
    }

    public function test_mute_posts_the_user_id_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->mute(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof MutePostRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/posts/7/mute'
                && $request->query()->get('user_id') === 42;
        });
    }

    public function test_unmute_deletes_with_the_user_id_query_parameter(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->unmute(7, 42);

        $mock->assertSent(function ($request): bool {
            return $request instanceof UnmutePostRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/posts/7/mute'
                && $request->query()->get('user_id') === 42;
        });
    }

    public function test_comments_returns_a_comment_collection_for_a_post(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->commentPayload(['id' => 1]), $this->commentPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $comments = $resource->comments(7, perPage: 10);

        $this->assertInstanceOf(CommentCollection::class, $comments);
        $this->assertCount(2, $comments);
        $this->assertSame([1, 2], $comments->map(static fn (Comment $comment): int => $comment->id)->all());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCommentsRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/comments'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_create_comment_posts_the_snake_case_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->commentPayload(['id' => 77]), 201)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $comment = $resource->createComment(7, new NewCommentData(text: 'Hello', replyToId: 54));

        $this->assertSame(77, $comment->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof CreateCommentRequest) {
                return false;
            }

            return $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/posts/7/comments'
                && $request->body()->all() === ['text' => 'Hello', 'reply_to_id' => 54];
        });
    }

    public function test_reactions_returns_a_typed_collection_for_a_post(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->reactionPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $reactions = $resource->reactions(7);

        $this->assertInstanceOf(ReactionCollection::class, $reactions);
        $this->assertCount(1, $reactions);
        $this->assertInstanceOf(Reaction::class, $reactions->first());

        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPostReactionsRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/reactions';
        });
    }

    public function test_react_posts_the_emoji_body(): void
    {
        $mock = new MockClient([MockResponse::make($this->reactionPayload(), 201)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $reaction = $resource->react(7, '👍');

        $this->assertSame('👍', $reaction->emoji);

        $mock->assertSent(function ($request): bool {
            return $request instanceof CreatePostReactionRequest
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/posts/7/reactions'
                && $request->body()->all() === ['emoji' => '👍'];
        });
    }

    public function test_remove_reaction_sends_a_delete_to_the_reactions_endpoint(): void
    {
        $mock = new MockClient([MockResponse::make([], 204)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $resource->removeReaction(7);

        $mock->assertSent(function ($request): bool {
            return $request instanceof DeletePostReactionRequest
                && $request->getMethod() === Method::DELETE
                && $request->resolveEndpoint() === 'networks/12345/posts/7/reactions';
        });
    }

    public function test_it_paginates_the_items_links_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->postPayload(['id' => 1])],
                'links' => ['self' => 'https://api.mn.co/...?page=1', 'next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->postPayload(['id' => 2])],
                'links' => ['self' => 'https://api.mn.co/...?page=2', 'next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(spaceId: 9, perPage: 50)->items() as $post) {
            $this->assertInstanceOf(Post::class, $post);
            $ids[] = $post->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request->query()->get('page') === 2
                && $request->query()->get('space_id') === 9
                && $request->query()->get('per_page') === 50;
        });
    }

    public function test_it_paginates_the_data_meta_envelope_and_terminates(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => [$this->postPayload(['id' => 1])],
                'meta' => ['current_page' => 1, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
            MockResponse::make([
                'data' => [$this->postPayload(['id' => 2])],
                'meta' => ['current_page' => 2, 'total_pages' => 2, 'total_count' => 2, 'per_page' => 1],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginate(perPage: 1)->items() as $post) {
            $this->assertInstanceOf(Post::class, $post);
            $ids[] = $post->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
    }

    public function test_each_runs_a_callback_over_every_post(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->postPayload(['id' => 1])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $ids = [];

        $resource->each(static function (Post $post) use (&$ids): void {
            $ids[] = $post->id;
        });

        $this->assertSame([1], $ids);
    }

    public function test_a_404_throws_a_not_found_exception(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Post not found'], 404)]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $this->expectException(NotFoundException::class);

        $resource->findById(7);
    }

    public function test_paginate_comments_yields_post_comments_across_pages(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'items' => [$this->commentPayload(['id' => 1])],
                'links' => ['next' => 'https://api.mn.co/...?page=2'],
            ], 200),
            MockResponse::make([
                'items' => [$this->commentPayload(['id' => 2])],
                'links' => ['next' => null],
            ], 200),
        ]);

        $resource = new PostsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginateComments(7, perPage: 10)->items() as $comment) {
            $this->assertInstanceOf(Comment::class, $comment);
            $ids[] = $comment->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request instanceof ListCommentsRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/comments'
                && $request->query()->get('per_page') === 10;
        });
    }

    public function test_paginate_reactions_yields_post_reactions_across_pages(): void
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

        $resource = new PostsResource($this->admin($mock), '12345');

        $ids = [];

        foreach ($resource->paginateReactions(7, perPage: 10)->items() as $reaction) {
            $this->assertInstanceOf(Reaction::class, $reaction);
            $ids[] = $reaction->id;
        }

        $this->assertSame([1, 2], $ids);
        $mock->assertSentCount(2);
        $mock->assertSent(function ($request): bool {
            return $request instanceof ListPostReactionsRequest
                && $request->resolveEndpoint() === 'networks/12345/posts/7/reactions'
                && $request->query()->get('per_page') === 10;
        });
    }
}
