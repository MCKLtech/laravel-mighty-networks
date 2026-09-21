<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The type of a post or article.
 *
 * Values mirror the lowercase `post_type` strings returned by the Admin REST API.
 */
enum PostType: string
{
    case Announcement = 'announcement';
    case Article = 'article';
    case Coursework = 'coursework';
    case Event = 'event';
    case EventPage = 'event_page';
    case Introduction = 'introduction';
    case Poll = 'poll';
    case Post = 'post';
    case Question = 'question';
    case QuizQuestion = 'quiz_question';
    case SpacePage = 'space_page';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Announcement => 'Announcement',
            self::Article => 'Article',
            self::Coursework => 'Coursework',
            self::Event => 'Event',
            self::EventPage => 'Event page',
            self::Introduction => 'Introduction',
            self::Poll => 'Poll',
            self::Post => 'Post',
            self::Question => 'Question',
            self::QuizQuestion => 'Quiz question',
            self::SpacePage => 'Space page',
        };
    }
}
