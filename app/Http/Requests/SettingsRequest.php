<?php

namespace App\Http\Requests;

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
            'timezone' => '',
            'layout' => '',
            'temperature_scale' => '',
            'locale' => '',
            'currency_id' => '',
        ];
    }
}
