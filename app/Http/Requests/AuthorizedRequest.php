<?php

namespace App\Http\Requests;

class AuthorizedRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}
