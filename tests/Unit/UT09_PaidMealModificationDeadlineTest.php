<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;

test('UT09 calculates the paid-meal modification deadline from the school setting', function () {
    $school = new School(['ordering_deadline_day' => 'wednesday']);

    $deadline = SchoolOrderingSchedule::deadlineForCollection(
        Carbon::parse('2026-08-17', 'Asia/Kuala_Lumpur'),
        $school,
    );

    expect($deadline->format('Y-m-d H:i:s'))->toBe('2026-08-12 23:59:59');
});
