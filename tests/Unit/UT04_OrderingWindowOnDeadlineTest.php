<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;

test('UT04 returns next week as the ordering window on the deadline day', function () {
    $school = new School(['ordering_deadline_day' => 'wednesday']);

    [$start, $end] = SchoolOrderingSchedule::orderingWindow(
        $school,
        Carbon::parse('2026-08-12 12:00:00', 'Asia/Kuala_Lumpur'),
    );

    // Confirms the next Monday-to-Sunday ordering window.
    expect($start->toDateString())->toBe('2026-08-17')
        ->and($end->toDateString())->toBe('2026-08-23');
});
