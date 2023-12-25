<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     */
    protected function passwordRules(bool $confirmed = true): array
    {
        $rules = Password::min(4);

        if (App::environment('production')) {
            // @codeCoverageIgnoreStart
            $rules->min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
            // @codeCoverageIgnoreEnd
        }

        return array_filter([
            'nullable',
            'string',
            $confirmed ? 'confirmed' : null,
            $rules,
        ]);
    }
}
