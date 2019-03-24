<?php

namespace App\Http\Requests\People;

use App\Http\Requests\AuthorizedRequest;

class DebtRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'in_debt' => 'required',
            'amount' => 'required|numeric',
            'reason' => 'string|nullable',
            'status' => '',
        ];
    }
}
