<?php

namespace App\Http\Requests\People;

use App\Http\Requests\AuthorizedRequest;

class GiftsRequest extends AuthorizedRequest
{
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
            'value' => 'integer|nullable',
            'has_recipient' => 'boolean',
            'recipient' => 'required_with:has_recipient',
        ];
    }
}
