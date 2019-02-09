<?php

namespace App\Http\Requests\People;

use App\Http\Requests\AuthorizedRequest;

class PetsRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'max:255|nullable',
            'pet_category_id' => 'integer',
        ];
    }
}
