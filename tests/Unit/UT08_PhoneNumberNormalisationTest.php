<?php

use App\Rules\MalaysianPhoneNumber;

test('UT08 normalises local and international forms to the same phone number', function () {
    $local = MalaysianPhoneNumber::normalize('012-3456789');
    $international = MalaysianPhoneNumber::normalize('+60123456789');
    $internationalWithoutPlus = MalaysianPhoneNumber::normalize('60123456789');

    expect($local)->toBe('0123456789')
        ->and($international)->toBe($local)
        ->and($internationalWithoutPlus)->toBe($local);
});
