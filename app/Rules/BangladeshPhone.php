<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Helpers\PhoneHelper;

class BangladeshPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!PhoneHelper::isValidBdPhone($value)) {
            $fail('The :attribute must be a valid Bangladesh phone number.');
        }
    }
}