<?php

namespace App\Http\Requests;

class PasswordChangeRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password_current' => 'required',
            'password' => 'required|confirmed|min:6',
        ];
    }
}
