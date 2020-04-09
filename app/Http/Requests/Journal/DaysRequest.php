<?php

namespace App\Http\Requests\Journal;

use App\Http\Requests\AuthorizedRequest;

class DaysRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rate' => 'integer|required',
        ];
    }
}
