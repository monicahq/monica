<?php

namespace App\Http\Resources\Contact;

use Illuminate\Http\Resources\Json\Resource;

class ContactShort extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'contact',
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'complete_name' => $this->getCompleteName(),
            'gender' => $this->gender,
            'is_partial' => (bool) $this->is_partial,
            'is_dead' => (bool) $this->is_dead,
            'information' => [
                'birthdate' => [
                    'is_age_based' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_age_based),
                    'is_year_unknown' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_year_unknown),
                    'date' => (is_null($this->birthdate) ? null : $this->birthdate->date->format(config('api.timestamp_format'))),
                ],
                'deceased_date' => [
                    'is_age_based' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_age_based),
                    'is_year_unknown' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_year_unknown),
                    'date' => (is_null($this->deceasedDate) ? null : $this->deceasedDate->date->format(config('api.timestamp_format'))),
                ],
                'avatar' => [
                    'avatar_external_url' => $this->avatar_external_url,
                    'avatar_file_name' => $this->avatar_file_name,
                    'avatar_location' => $this->avatar_location,
                ],
            ],
            'account' => [
                'id' => $this->account->id,
            ],
        ];
    }
}
