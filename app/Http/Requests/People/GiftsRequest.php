<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class GiftsRequest extends FormRequest
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
            'name' => 'required',
            'comment' => '',
            'url' => '',
            'offered' => 'string',
            'date_offered' => 'date|nullable',
            'value' => 'int|nullable',
            'has_recipient' => 'boolean',
            'recipient' => 'required_with:has_recipient',
        ];
    }
}
