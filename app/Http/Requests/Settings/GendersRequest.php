<?php

namespace App\Http\Requests\Settings;

use App\Models\Contact\Gender;
use Illuminate\Validation\Rule;
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
            'type' => ['required', Rule::in(Gender::LIST)],
            'name' => 'max:255',
            'id' => 'integer',
        ];
    }
}
