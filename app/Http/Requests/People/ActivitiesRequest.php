<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class ActivitiesRequest extends FormRequest
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
            'contacts' => 'required',
            'summary' => 'required',
            'date_it_happened' => 'required|date',
            'description' => 'string|nullable',
            'activity_type_id' => 'int|nullable',
        ];
    }
}
