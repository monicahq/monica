<?php

namespace App\Http\Requests\People;

use App\Http\Requests\AuthorizedRequest;

class ActivitiesRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contacts' => 'required|array',
            'summary' => 'required',
            'date_it_happened' => 'required|date',
            'description' => 'string|nullable',
            'activity_type_id' => 'int|nullable',
        ];
    }
}
