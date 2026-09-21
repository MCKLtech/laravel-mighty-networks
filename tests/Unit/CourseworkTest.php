<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Unit;

use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCourseworkData;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCourseworkData;
use MCKLtech\MightyNetworks\Enums\CompletionCriteria;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Enums\UnlockingCriteria;
use MCKLtech\MightyNetworks\Tests\TestCase;

final class CourseworkTest extends TestCase
{
    public function test_coursework_from_array_maps_all_fields(): void
    {
        $coursework = Coursework::fromArray([
            'id' => 100,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'space_id' => 9,
            'type' => 'lesson',
            'parent_id' => 55,
            'parent_type' => 'section',
            'title' => 'Welcome',
            'description' => 'Intro lesson',
            'status' => 'posted',
            'position' => 1,
            'completion_criteria' => 'visited',
            'unlocking_criteria' => 'sequential',
            'children_count' => 0,
            'permalink' => 'https://example.mn.co/courses/9/lessons/100',
        ]);

        $this->assertSame(100, $coursework->id);
        $this->assertSame(9, $coursework->spaceId);
        $this->assertSame(CourseworkType::Lesson, $coursework->type);
        $this->assertSame(55, $coursework->parentId);
        $this->assertSame('section', $coursework->parentType);
        $this->assertSame('Welcome', $coursework->title);
        $this->assertSame('Intro lesson', $coursework->description);
        $this->assertSame(CourseworkStatus::Posted, $coursework->status);
        $this->assertSame(CompletionCriteria::Visited, $coursework->completionCriteria);
        $this->assertSame(UnlockingCriteria::Sequential, $coursework->unlockingCriteria);
        $this->assertSame(0, $coursework->childrenCount);
        $this->assertSame('https://example.mn.co/courses/9/lessons/100', $coursework->permalink);
    }

    public function test_coursework_from_array_falls_back_on_unknown_enum_values(): void
    {
        $coursework = Coursework::fromArray([
            'id' => 100,
            'created_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-03-20T14:22:00+00:00',
            'space_id' => 9,
            'type' => 'something-new',
            'title' => 'Mystery',
            'status' => 'surprise',
            'position' => 1,
            'completion_criteria' => 'unknown',
            'unlocking_criteria' => 'unknown',
            'children_count' => 0,
            'permalink' => 'https://example.mn.co/x',
        ]);

        $this->assertSame(CourseworkType::Lesson, $coursework->type);
        $this->assertSame(CourseworkStatus::Hidden, $coursework->status);
        $this->assertSame(CompletionCriteria::None, $coursework->completionCriteria);
        $this->assertSame(UnlockingCriteria::None, $coursework->unlockingCriteria);
        $this->assertNull($coursework->parentId);
        $this->assertNull($coursework->description);
    }

    public function test_new_coursework_data_serializes_enums_and_omits_nulls(): void
    {
        $data = new NewCourseworkData(
            type: CourseworkType::Quiz,
            title: 'Pop Quiz',
            status: CourseworkStatus::Posted,
            completionCriteria: CompletionCriteria::MinimumCorrectPercentage,
            unlockingCriteria: UnlockingCriteria::ScheduledDate,
        );

        $this->assertSame([
            'type' => 'quiz',
            'title' => 'Pop Quiz',
            'status' => 'posted',
            'completion_criteria' => 'minimum_correct_percentage',
            'unlocking_criteria' => 'scheduled_date',
        ], $data->toArray());
    }

    public function test_new_coursework_data_accepts_raw_strings(): void
    {
        $data = new NewCourseworkData(type: 'lesson', parentId: 55);

        $this->assertSame(['type' => 'lesson', 'parent_id' => 55], $data->toArray());
    }

    public function test_update_coursework_data_omits_nulls(): void
    {
        $data = new UpdateCourseworkData(
            title: 'Renamed',
            status: CourseworkStatus::Hidden,
        );

        $this->assertSame(['title' => 'Renamed', 'status' => 'hidden'], $data->toArray());
        $this->assertSame([], (new UpdateCourseworkData)->toArray());
    }

    public function test_coursework_enums_have_the_documented_values(): void
    {
        $this->assertSame(['lesson', 'quiz', 'section', 'overview'], array_column(CourseworkType::cases(), 'value'));
        $this->assertSame(['posted', 'hidden', 'pending'], array_column(CourseworkStatus::cases(), 'value'));
        $this->assertSame(
            ['none', 'visited', 'button', 'video', 'minimum_correct_percentage'],
            array_column(CompletionCriteria::cases(), 'value'),
        );
        $this->assertSame(
            ['none', 'sequential', 'time_from_course_join', 'scheduled_date'],
            array_column(UnlockingCriteria::cases(), 'value'),
        );
    }

    public function test_coursework_enums_have_friendly_labels(): void
    {
        $this->assertSame('Lesson', CourseworkType::Lesson->toFriendly());
        $this->assertSame('Pending', CourseworkStatus::Pending->toFriendly());
        $this->assertSame('Minimum Correct Percentage', CompletionCriteria::MinimumCorrectPercentage->toFriendly());
        $this->assertSame('Time From Course Join', UnlockingCriteria::TimeFromCourseJoin->toFriendly());
    }
}
