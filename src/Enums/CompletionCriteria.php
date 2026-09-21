<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * How completion of a coursework item is tracked.
 */
enum CompletionCriteria: string
{
    case None = 'none';
    case Visited = 'visited';
    case Button = 'button';
    case Video = 'video';
    case MinimumCorrectPercentage = 'minimum_correct_percentage';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Visited => 'Visited',
            self::Button => 'Button Clicked',
            self::Video => 'Video Watched',
            self::MinimumCorrectPercentage => 'Minimum Correct Percentage',
        };
    }
}
