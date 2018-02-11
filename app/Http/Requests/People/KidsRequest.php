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
            'first_name' => 'required|string|max:50',
            'last_name' => 'string|nullable|max:100',
            'gender_id' => 'integer|required',
            'birthdate' => 'required|in:unknown,approximate,exact',
            'birthdate_year' => 'int|nullable',
            'birthdate_month' => 'int|nullable',
            'birthdate_day' => 'int|nullable',
            'age' => 'int|nullable',
        ];
    }
}
