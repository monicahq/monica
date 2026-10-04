<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Validation\Rules\Password;

class PasswordRequirements
{
    public static function passwordRules(): string
    {
        $rules = Password::default()->appliedRules();

        $requirements = ["minlength: {$rules['min']}"];

        if ($rules['mixedCase']) {
            $requirements[] = 'required: lower';
            $requirements[] = 'required: upper';
        }

        if ($rules['letters'] && ! $rules['mixedCase']) {
            $requirements[] = 'required: lower, upper';
        }

        if ($rules['numbers']) {
            $requirements[] = 'required: digit';
        }

        if ($rules['symbols']) {
            $requirements[] = 'required: special';
        }

        return implode('; ', $requirements).';';
    }

    public static function description(): string
    {
        $rules = Password::default()->appliedRules();

        $sentences = [trans_choice('Minimum :count character.|Minimum :count characters.', $rules['min'])];

        if ($rules['mixedCase']) {
            $sentences[] = __('Upper and lower case letters.');
        }

        if ($rules['letters'] && ! $rules['mixedCase']) {
            $sentences[] = __('At least one letter.');
        }

        if ($rules['numbers']) {
            $sentences[] = __('At least one number.');
        }

        if ($rules['symbols']) {
            $sentences[] = __('At least one symbol.');
        }

        return implode(' ', $sentences);
    }
}
