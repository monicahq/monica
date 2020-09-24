<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest as Middleware;

use Illuminate\Support\Facades\Log;

class SanitizeInput extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected function transform($key, $value)
    {

        Log::critical($key);

        if (in_array($key, $this->except, true)) {
            return $value;
        }
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

