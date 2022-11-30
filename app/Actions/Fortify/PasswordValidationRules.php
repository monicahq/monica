<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\App;
use Laravel\Fortify\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @param  bool  $confirmed
     * @return array
     */
    protected function passwordRules(bool $confirmed = true): array
    {
        $rules = (new Password)->length(4);

        if (App::environment('production')) {
            // @codeCoverageIgnoreStart
            $rules->length(8)
                ->requireUppercase()
                ->requireNumeric();
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
