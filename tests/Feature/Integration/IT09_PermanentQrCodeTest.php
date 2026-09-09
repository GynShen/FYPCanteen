<?php

use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT09 returns different permanent QR images for different students', function () {
    $school = F::school();
    $operator = F::user('operator', $school);
    $first = F::student($school);
    $second = F::student($school);

    $firstResponse = $this->actingAs($operator)->get(route('owner.student.qr-code', $first));
    $secondResponse = $this->actingAs($operator)->get(route('owner.student.qr-code', $second));

    $firstResponse->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    $secondResponse->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    expect($first->qr_code)->not->toBe($second->qr_code)
        ->and($firstResponse->getContent())->not->toBe($secondResponse->getContent());
});
