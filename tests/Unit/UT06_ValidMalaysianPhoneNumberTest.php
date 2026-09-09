<?php

use App\Rules\MalaysianPhoneNumber;

test('UT06 accepts valid Malaysian mobile-number formats', function () {
    $validNumbers = [
        '012-3456789',
        '0123456789',
        '+60123456789',
        '60123456789',
        '011-12345678',
        '01512345678',
    ];

    foreach ($validNumbers as $number) {
        $failed = false;

        (new MalaysianPhoneNumber())->validate(
            'phone_number',
            $number,
            function () use (&$failed): void {
                $failed = true;
            },
        );

        expect($failed)->toBeFalse("Expected {$number} to be accepted.");
    }
});
