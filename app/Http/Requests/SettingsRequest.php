<?php

namespace App\Http\Requests;

use App\Models\User\User;
use Illuminate\Validation\Rule;

class SettingsRequest extends AuthorizedRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$this->id,
            'timezone' => 'required|string',
            'fluid_container' => 'required|bool',
            'temperature_scale' => [
                'required',
                'string',
                Rule::in(['fahrenheit', 'celsius']),
            ],
            'locale' => [
                'required',
                'string',
                Rule::In(config('lang-detector.languages')),
            ],
            'currency_id' => 'required|int|exists:currencies,id',
            'name_order' => [
                'required',
                'string',
                Rule::In(User::NAMES_ORDER),
            ],
        ];
    }
}
