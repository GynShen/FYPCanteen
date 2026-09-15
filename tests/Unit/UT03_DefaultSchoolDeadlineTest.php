<?php

use App\Models\School;
use App\Support\SchoolOrderingSchedule;

test('UT03 defaults a missing or invalid school deadline to Saturday', function () {
    $missingDeadline = new School;
    $invalidDeadline = new School(['ordering_deadline_day' => 'holiday']);

    // Confirms that missing and invalid deadlines default to Saturday.
    expect(SchoolOrderingSchedule::deadlineDay($missingDeadline))->toBe('saturday')
        ->and(SchoolOrderingSchedule::deadlineDay($invalidDeadline))->toBe('saturday');
});
