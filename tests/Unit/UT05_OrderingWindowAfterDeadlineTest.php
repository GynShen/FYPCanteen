<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;

test('UT05 uses the next deadline when the current deadline has passed', function () {
    $school = new School(['ordering_deadline_day' => 'monday']);

    [$start, $end] = SchoolOrderingSchedule::orderingWindow(
        $school,
        Carbon::parse('2026-09-15 00:00:00', 'Asia/Kuala_Lumpur'),
    );

    // Confirms the expected Tuesday-to-Monday ordering period.
    expect($start->toDateString())->toBe('2026-09-22')
        ->and($end->toDateString())->toBe('2026-09-28');
});
