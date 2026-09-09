<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;

test('UT05 moves the ordering window forward after the deadline day', function () {
    $school = new School(['ordering_deadline_day' => 'wednesday']);

    [$start, $end] = SchoolOrderingSchedule::orderingWindow(
        $school,
        Carbon::parse('2026-08-13 00:00:00', 'Asia/Kuala_Lumpur'),
    );

    expect($start->toDateString())->toBe('2026-08-24')
        ->and($end->toDateString())->toBe('2026-08-30');
});
