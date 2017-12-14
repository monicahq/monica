<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class ContactFieldsRequest extends FormRequest
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
            'contact_field_type_id' => 'required',
            'data' => 'max:255|required',
        ];
    }
}
