<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class RemindersRequest extends FormRequest
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
            'title' => 'required',
            'next_expected_date' => 'required|date',
            'description' => 'string|nullable',
            'frequency_type' => 'required',
            'frequency_number' => 'int|nullable',
        ];
    }
}
