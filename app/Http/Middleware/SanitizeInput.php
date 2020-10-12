<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest as Middleware;

class SanitizeInput extends Middleware
{
    /**
     * Extends TransformsRequest to clean input from XSS
     *
     */
    protected function transform($key, $value)
    {
        // Ignore excepted ones
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        // Strip Html tags and encode missed ones
        if (is_string($value) && $value !== '') {
            $value = strip_tags($value);
            $value = htmlentities($value, ENT_QUOTES, 'utf-8');
        }
        return $value;
    }

    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
        '_token',
        ''
    ];

}

