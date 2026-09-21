<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * The type of poll or question to create in a Network.
 *
 * - `MultipleChoice`: a list of selectable choices.
 * - `HotCold`: a continuous slider between two extremes.
 * - `Percentage`: allocate percentages across choices.
 * - `Question`: an open-ended text response.
 */
enum PollType: string
{
    case MultipleChoice = 'multiple_choice';
    case HotCold = 'hot_cold';
    case Percentage = 'percentage';
    case Question = 'question';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple choice',
            self::HotCold => 'Hot/cold',
            self::Percentage => 'Percentage',
            self::Question => 'Question',
        };
    }
}
