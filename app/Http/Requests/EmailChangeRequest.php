<?php

namespace App\Http\Requests;

class EmailChangeRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

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
