<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class Contact extends Resource
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
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'last_talked_to' => $this->last_talked_to,
            'information' => [
                'family' => [
                    'kids' => [
                        'total' => $this->getOffsprings()->count(),
                        'kids' => $this->getOffsprings(),
                    ],
                    'significant_others' => [
                        'total' => $this->getCurrentPartners()->count(),
                        'significant_others' => $this->getCurrentPartners(),
                    ],
                ],
                'dates' => [
                    [
                        'name' => 'birthdate',
                        'is_birthdate_approximate' => $this->is_birthdate_approximate,
                        'birthdate' => $this->birthdate,
                    ],
                ],
                'career' => [
                    'job' => $this->job,
                    'company' => $this->company,
                ],
                'avatar' => [
                    'gravatar_url' => $this->getGravatar(110),
                ],
                'food_preferencies' => $this->food_preferencies,
            ],
            'contact' => [
                'emails' => [
                    [
                        'name' => 'personal',
                        'email' => $this->email,
                    ],
                ],
                'phone_numbers' => [
                    [
                        'name' => 'home',
                        'phone_number' => $this->phone_number,
                    ],
                ],
                'social_network' => [
                    'facebook_profile_url' => $this->facebook_profile_url,
                    'twitter_profile_url' => $this->twitter_profile_url,
                    'linkedin_profile_url' => $this->linkedin_profile_url,
                ],
                'addresses' => [
                    [
                        'name' => 'home',
                        'street' => $this->street,
                        'city' => $this->city,
                        'province' => $this->province,
                        'postal_code' => $this->postal_code,
                        'country_id' => $this->country_id,
                        'country_name' => $this->getCountryName(),
                    ],
                ],
            ],
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        //return parent::toArray($request);
    }
}
