<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class RelationshipsRequest extends FormRequest
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'string|nullable|max:100',
            'gender' => 'in:male,female,none',
            'status' => 'in:active,past|nullable',
            'is_birthdate_approximate' => 'required|in:unknown,approximate,exact',
            'birthdate' => 'date|nullable',
            'age' => 'int|nullable',
        ];
    }
}
