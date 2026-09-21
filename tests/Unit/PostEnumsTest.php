<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\Enums\PostStatus;
use MCKLtech\MightyNetworks\Enums\PostType;
use PHPUnit\Framework\TestCase;

final class PostEnumsTest extends TestCase
{
    public function test_post_type_has_every_documented_value(): void
    {
        $this->assertSame([
            'announcement',
            'article',
            'coursework',
            'event',
            'event_page',
            'introduction',
            'poll',
            'post',
            'question',
            'quiz_question',
            'space_page',
        ], array_map(static fn (PostType $type): string => $type->value, PostType::cases()));

        $this->assertSame(PostType::Article, PostType::from('article'));
        $this->assertSame('Event page', PostType::EventPage->toFriendly());
        $this->assertSame('Quiz question', PostType::QuizQuestion->toFriendly());
    }

    public function test_post_status_has_every_documented_value(): void
    {
        $this->assertSame(['draft', 'posted', 'scheduled'], array_map(
            static fn (PostStatus $status): string => $status->value,
            PostStatus::cases(),
        ));

        $this->assertSame(PostStatus::Posted, PostStatus::from('posted'));
        $this->assertSame('Draft', PostStatus::Draft->toFriendly());
        $this->assertSame('Scheduled', PostStatus::Scheduled->toFriendly());
    }
}
