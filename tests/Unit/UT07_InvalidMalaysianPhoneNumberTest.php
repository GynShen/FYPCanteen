<?php

use App\Rules\MalaysianPhoneNumber;

test('UT07 rejects invalid Malaysian mobile-number formats', function () {
    $invalidNumbers = [
        '012-345678',
        '012-34567890',
        '011-1234567',
        '019|1234567',
        'phone0123456789',
        '0212345678',
    ];

    foreach ($invalidNumbers as $number) {
        $failed = false;

        (new MalaysianPhoneNumber)->validate(
            'phone_number',
            $number,
            function () use (&$failed): void {
                $failed = true;
            },
        );

        // Confirms that this invalid phone format fails validation.
        expect($failed)->toBeTrue("Expected {$number} to be rejected.");
    }
});
