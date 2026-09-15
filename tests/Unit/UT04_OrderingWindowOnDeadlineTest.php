<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;

test('UT04 starts the ordering window after the deadline day', function () {
    $school = new School(['ordering_deadline_day' => 'wednesday']);

    [$start, $end] = SchoolOrderingSchedule::orderingWindow(
        $school,
        Carbon::parse('2026-08-12 12:00:00', 'Asia/Kuala_Lumpur'),
    );

    // Confirms the seven-day period immediately after the deadline.
    expect($start->toDateString())->toBe('2026-08-13')
        ->and($end->toDateString())->toBe('2026-08-19');
});
