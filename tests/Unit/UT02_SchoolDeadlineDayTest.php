<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;

test('UT02 returns the configured school ordering deadline day', function () {
    $school = new School(['ordering_deadline_day' => 'wednesday']);

    expect(SchoolOrderingSchedule::deadlineDay($school))->toBe('wednesday');
});
