<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     */
    protected function passwordRules(bool $confirmed = true): array
    {
        return array_filter([
            'nullable',
            'string',
            $confirmed ? 'confirmed' : null,
            Password::default(),
        ]);
    }
}
