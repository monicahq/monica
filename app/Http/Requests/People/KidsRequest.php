<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class KidsRequest extends FormRequest
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
            'first_name' => 'required|string',
            'gender' => 'in:male,female,none',
            'is_birthdate_approximate' => 'required|in:unknown,approximate,exact',
            'birthdate' => 'date|nullable',
            'food_preferences' => '',
            'age' => 'int|nullable',
        ];
    }
}
