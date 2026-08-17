<?php

namespace Tests\Unit;

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SchoolOrderingScheduleTest extends TestCase
{
    public function test_ordering_remains_on_next_week_during_the_school_deadline_day(): void
    {
        $school = new School(['ordering_deadline_day' => 'wednesday']);
        [$start, $end] = SchoolOrderingSchedule::orderingWindow(
            $school,
            Carbon::parse('2026-08-12 12:00:00', 'Asia/Kuala_Lumpur'),
        );

        $this->assertSame('2026-08-17', $start->toDateString());
        $this->assertSame('2026-08-23', $end->toDateString());
    }

    public function test_ordering_moves_forward_one_more_week_after_the_deadline_day(): void
    {
        $school = new School(['ordering_deadline_day' => 'wednesday']);
        [$start, $end] = SchoolOrderingSchedule::orderingWindow(
            $school,
            Carbon::parse('2026-08-13 00:00:00', 'Asia/Kuala_Lumpur'),
        );

        $this->assertSame('2026-08-24', $start->toDateString());
        $this->assertSame('2026-08-30', $end->toDateString());
    }

    public function test_paid_meal_change_deadline_uses_the_school_setting(): void
    {
        $school = new School(['ordering_deadline_day' => 'wednesday']);
        $deadline = SchoolOrderingSchedule::deadlineForCollection(
            Carbon::parse('2026-08-17', 'Asia/Kuala_Lumpur'),
            $school,
        );

        $this->assertSame('2026-08-12 23:59:59', $deadline->format('Y-m-d H:i:s'));
        $this->assertSame('Thursday', SchoolOrderingSchedule::preparationStartDay($school));
    }
}
