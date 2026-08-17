<?php

namespace App\Support;

use App\Models\School;
use Carbon\Carbon;

class SchoolOrderingSchedule
{
    public const DAYS = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7,
    ];

    public static function deadlineDay(?School $school): string
    {
        $day = strtolower((string) ($school?->ordering_deadline_day ?: 'saturday'));

        return array_key_exists($day, self::DAYS) ? $day : 'saturday';
    }

    /** @return array{Carbon, Carbon} */
    public static function orderingWindow(School $school, ?Carbon $now = null): array
    {
        $now ??= Carbon::now('Asia/Kuala_Lumpur');
        $cutoffIsoDay = self::DAYS[self::deadlineDay($school)];
        $weeksAhead = $now->dayOfWeekIso <= $cutoffIsoDay ? 1 : 2;
        $start = $now->copy()->startOfWeek(Carbon::MONDAY)->addWeeks($weeksAhead)->startOfDay();

        return [$start, $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()];
    }

    public static function deadlineForCollection(Carbon $collectionDate, School $school): Carbon
    {
        $cutoffIsoDay = self::DAYS[self::deadlineDay($school)];

        return $collectionDate->copy()
            ->startOfWeek(Carbon::MONDAY)
            ->subDays(8 - $cutoffIsoDay)
            ->endOfDay();
    }

    public static function preparationStartDay(School $school): string
    {
        $cutoffIsoDay = self::DAYS[self::deadlineDay($school)];
        $nextIsoDay = $cutoffIsoDay === 7 ? 1 : $cutoffIsoDay + 1;

        return ucfirst(array_search($nextIsoDay, self::DAYS, true));
    }
}
