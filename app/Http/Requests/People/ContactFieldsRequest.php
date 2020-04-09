<?php

namespace App\Http\Requests\People;

use App\Http\Requests\AuthorizedRequest;

class ContactFieldsRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contact_field_type_id' => 'required|integer',
            'data' => 'max:255|required',
        ];
    }
}
