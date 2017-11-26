<?php

namespace App\Http\Resources\Contact;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

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
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'is_partial' => (bool) $this->is_partial,
            'is_dead' => (bool) $this->is_dead,
            'deceased_date' => (is_null($this->deceased_date) ? null : $this->deceased_date->format(config('api.timestamp_format'))),
            'last_called' => $this->when(! $this->is_partial, (is_null($this->last_called) ? null : (string) $this->last_called)),
            'last_talked_to' => $this->when(! $this->is_partial, (is_null($this->last_talked_to) ? null : (string) $this->last_talked_to)),
            'information' => [
                'family' => $this->when(! $this->is_partial, [
                    'kids' => [
                        'total' => $this->getOffsprings()->count(),
                        'kids' => $this->getOffspringsForAPI(),
                    ],
                    'partners' => [
                        'total' => $this->getCurrentPartners()->count(),
                        'partners' => $this->getCurrentPartnersForAPI(),
                    ],
                    'progenitors' => [
                        'total' => $this->getProgenitors()->count(),
                        'progenitors' => $this->getProgenitorsForAPI(),
                    ],
                ]),
                'dates' => [
                    [
                        'name' => 'birthdate',
                        'is_birthdate_approximate' => $this->is_birthdate_approximate,
                        'birthdate' => (is_null($this->birthdate) ? null : $this->birthdate->format(config('api.timestamp_format'))),
                    ],
                ],
                'career' => $this->when(! $this->is_partial, [
                    'job' => $this->job,
                    'company' => $this->company,
                ]),
                'avatar' => [
                    'gravatar_url' => $this->getGravatar(110),
                ],
                'food_preferencies' => $this->when(! $this->is_partial, $this->food_preferencies),
                'how_you_met' => [
                    'general_information' => $this->first_met_additional_info,
                    'first_met_date' => (is_null($this->first_met) ? null : $this->first_met->format(config('api.timestamp_format'))),
                    'first_met_through_contact' => new ContactShortResource($this->getIntroducer()),
                ],
            ],
            'contact' => $this->when(! $this->is_partial, [
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
            ]),
            'addresses' => $this->when(! $this->is_partial, $this->getAddressesForAPI()),
            'tags' => $this->when(! $this->is_partial, $this->getTagsForAPI()),
            'statistics' => $this->when(! $this->is_partial, [
                'number_of_calls' => $this->calls->count(),
                'number_of_notes' => $this->notes->count(),
                'number_of_activities' => $this->activities->count(),
                'number_of_reminders' => $this->reminders->count(),
                'number_of_tasks' => $this->tasks->count(),
                'number_of_gifts' => $this->gifts->count(),
                'number_of_debts' => $this->debts->count(),
            ]),
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
