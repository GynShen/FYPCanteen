<?php

namespace Tests\Unit;

use App\Rules\MalaysianPhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MalaysianPhoneNumberTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            ['012-3456789'],
            ['0123456789'],
            ['+60123456789'],
            ['60123456789'],
            ['011-12345678'],
            ['01512345678'],
        ];
    }

    public static function invalidNumbers(): array
    {
        return [
            ['012-345678'],
            ['012-34567890'],
            ['011-1234567'],
            ['019|1234567'],
            ['phone0123456789'],
            ['0212345678'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_it_accepts_valid_malaysian_mobile_numbers(string $number): void
    {
        $failed = false;
        (new MalaysianPhoneNumber)->validate('phone_number', $number, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_invalid_malaysian_mobile_numbers(string $number): void
    {
        $failed = false;
        (new MalaysianPhoneNumber)->validate('phone_number', $number, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}
