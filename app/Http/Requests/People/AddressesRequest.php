<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class AddressesRequest extends FormRequest
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
            'country' => 'max:3|nullable',
            'name' => 'max:255|nullable',
            'street' => 'max:255|nullable',
            'city' => 'max:255|nullable',
            'province' => 'max:255|nullable',
            'postal_code' => 'max:255|nullable',
        ];
    }
}
