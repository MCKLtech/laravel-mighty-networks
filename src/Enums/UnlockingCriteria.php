<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * When a coursework item becomes available to a member.
 */
enum UnlockingCriteria: string
{
    case None = 'none';
    case Sequential = 'sequential';
    case TimeFromCourseJoin = 'time_from_course_join';
    case ScheduledDate = 'scheduled_date';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Sequential => 'Sequential',
            self::TimeFromCourseJoin => 'Time From Course Join',
            self::ScheduledDate => 'Scheduled Date',
        };
    }
}
