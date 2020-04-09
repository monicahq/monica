<?php

namespace App\Http\Requests;

class EmailChangeRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'newmail' => 'required|email|max:255|unique:users,email',
        ];
    }
}
