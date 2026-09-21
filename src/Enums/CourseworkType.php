<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The type of a coursework item within a course Space.
 */
enum CourseworkType: string
{
    case Lesson = 'lesson';
    case Quiz = 'quiz';
    case Section = 'section';
    case Overview = 'overview';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Lesson => 'Lesson',
            self::Quiz => 'Quiz',
            self::Section => 'Section',
            self::Overview => 'Overview',
        };
    }
}
