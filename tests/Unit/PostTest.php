<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\NewPostData;
use MCKLtech\MightyNetworks\DataTransferObjects\Post;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdatePostData;
use MCKLtech\MightyNetworks\Enums\PostStatus;
use MCKLtech\MightyNetworks\Enums\PostType;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
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

    public function test_it_maps_every_documented_field(): void
    {
        $post = Post::fromArray($this->payload());

        $this->assertSame(7, $post->id);
        $this->assertSame(42, $post->creatorId);
        $this->assertSame(9, $post->spaceId);
        $this->assertSame('A short summary', $post->summary);
        $this->assertSame('The full description', $post->description);
        $this->assertSame(PostType::Article, $post->postType);
        $this->assertSame(['https://cdn.mn.co/posts/7.jpg'], $post->images);
        $this->assertSame('Hello community', $post->title);
        $this->assertSame(PostStatus::Posted, $post->status);
        $this->assertSame('article', $post->contentType);
        $this->assertTrue($post->commentsEnabled);
        $this->assertSame('https://example.mn.co/posts/7', $post->permalink);
    }

    public function test_it_parses_dates_as_immutable_carbon_instances(): void
    {
        $post = Post::fromArray($this->payload());

        $this->assertInstanceOf(CarbonImmutable::class, $post->createdAt);
        $this->assertSame('2024-01-15T10:30:00+00:00', $post->createdAt->toIso8601String());
        $this->assertSame('2024-04-01T08:00:00+00:00', $post->lastActivityAt->toIso8601String());
    }

    public function test_it_falls_back_for_unknown_enum_values(): void
    {
        $post = Post::fromArray($this->payload(['post_type' => 'mystery', 'status' => 'mystery']));

        $this->assertSame(PostType::Post, $post->postType);
        $this->assertSame(PostStatus::Posted, $post->status);
    }

    public function test_new_post_data_emits_snake_case_and_omits_nulls(): void
    {
        $data = new NewPostData(
            spaceId: 9,
            title: 'Hello',
            postType: PostType::Article,
        );

        $this->assertSame([
            'space_id' => 9,
            'title' => 'Hello',
            'post_type' => 'article',
        ], $data->toArray());

        $this->assertSame('poll', (new NewPostData(spaceId: 1, title: 'T', postType: 'poll'))->toArray()['post_type']);
    }

    public function test_update_post_data_omits_null_fields(): void
    {
        $this->assertSame(['title' => 'New title'], (new UpdatePostData(title: 'New title'))->toArray());
        $this->assertSame([], (new UpdatePostData)->toArray());
    }
}
