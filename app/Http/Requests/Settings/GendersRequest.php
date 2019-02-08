<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\AuthorizedRequest;

class GendersRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'max:255',
            'id' => 'integer',
        ];
    }
}
