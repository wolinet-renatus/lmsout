<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces strong password complexity per SCMIS SRS Section 5.1.1:
 * minimum 8 characters with uppercase, lowercase, numeric, and special characters.
 * @author Renatus Bernardo
 */
class StrongPassword implements ValidationRule
{
    /**
     * Validate.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if (strlen($password) < 8) {
            $fail('The :attribute must be at least 8 characters long.');
            return;
        }

        if (! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/[0-9]/', $password)
            || ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $fail('The :attribute must contain uppercase, lowercase, number, and special characters.');
        }
    }
}
