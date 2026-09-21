<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\DataTransferObjects\Comment;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCommentData;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
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
            'files' => [['id' => 1, 'url' => 'https://cdn.mn.co/file.png']],
            'embedded_link' => ['url' => 'https://example.com'],
            'permalink' => 'https://example.mn.co/posts/7/comments/55',
        ], $overrides);
    }

    public function test_it_maps_every_documented_field(): void
    {
        $comment = Comment::fromArray($this->payload());

        $this->assertSame(55, $comment->id);
        $this->assertSame(7, $comment->targetableId);
        $this->assertSame('Post', $comment->targetableType);
        $this->assertSame('Nice post!', $comment->text);
        $this->assertTrue($comment->replyable);
        $this->assertSame(1, $comment->depth);
        $this->assertSame(3, $comment->cheerCount);
        $this->assertSame(1, $comment->replyCount);
        $this->assertNull($comment->replyToId);
        $this->assertSame(42, $comment->authorId);
        $this->assertSame(9, $comment->spaceId);
        $this->assertSame([['id' => 1, 'url' => 'https://cdn.mn.co/file.png']], $comment->files);
        $this->assertSame(['url' => 'https://example.com'], $comment->embeddedLink);
        $this->assertSame('https://example.mn.co/posts/7/comments/55', $comment->permalink);
        $this->assertInstanceOf(CarbonImmutable::class, $comment->createdAt);
    }

    public function test_it_maps_a_reply_and_omits_absent_optionals(): void
    {
        $comment = Comment::fromArray($this->payload([
            'reply_to_id' => 54,
            'files' => null,
            'embedded_link' => null,
        ]));

        $this->assertSame(54, $comment->replyToId);
        $this->assertSame([], $comment->files);
        $this->assertNull($comment->embeddedLink);
    }

    public function test_new_comment_data_emits_snake_case_and_omits_nulls(): void
    {
        $this->assertSame(['text' => 'Hello'], (new NewCommentData(text: 'Hello'))->toArray());
        $this->assertSame([
            'text' => 'Hello',
            'reply_to_id' => 54,
        ], (new NewCommentData(text: 'Hello', replyToId: 54))->toArray());
    }
}
