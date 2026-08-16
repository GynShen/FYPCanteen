<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MalaysianPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[+()\s\d-]+$/', $value) !== 1) {
            $fail('The :attribute must be a valid Malaysian mobile number.');

            return;
        }

        $phoneNumber = self::normalize($value);

        // 011 and 015 use eight subscriber digits; other 01X prefixes use seven.
        if ($phoneNumber === null || preg_match('/^01(?:[15]\d{8}|[02346789]\d{7})$/', $phoneNumber) !== 1) {
            $fail('Enter a valid Malaysian mobile number, for example 012-3456789.');
        }
    }

    public static function normalize(?string $value): ?string
    {
        if ($value !== null && preg_match('/^[+()\s\d-]+$/', $value) !== 1) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '60')
            ? '0'.substr($digits, 2)
            : $digits;
    }
}
