<?php

namespace App\Http\Resources\Contact;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Contact extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'contact',
            'hash_id' => $this->getHashId(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nickname' => $this->nickname,
            'description' => $this->description,
            'gender' => $this->gender->name,
            'is_starred' => (bool) $this->is_starred,
            'is_partial' => (bool) $this->is_partial,
            'is_active' => (bool) $this->is_active,
            'is_dead' => (bool) $this->is_dead,
            'last_called' => $this->when(! $this->is_partial, $this->getLastCalled()),
            'last_activity_together' => $this->when(! $this->is_partial, $this->getLastActivityDate()),
            'stay_in_touch_frequency' => $this->when(! $this->is_partial, $this->stay_in_touch_frequency),
            'stay_in_touch_trigger_date' => $this->when(! $this->is_partial, DateHelper::getTimestamp($this->stay_in_touch_trigger_date)),
            'information' => [
                'relationships' => $this->when(! $this->is_partial, [
                    'love' => [
                        'total' => (is_null($this->getRelationshipsByRelationshipTypeGroup('love')) ? 0 : $this->getRelationshipsByRelationshipTypeGroup('love')->count()),
                        'contacts' => (is_null($this->getRelationshipsByRelationshipTypeGroup('love')) ? null : \App\Models\Contact\Contact::translateForAPI($this->getRelationshipsByRelationshipTypeGroup('love'))),
                    ],
                    'family' => [
                        'total' => (is_null($this->getRelationshipsByRelationshipTypeGroup('family')) ? 0 : $this->getRelationshipsByRelationshipTypeGroup('family')->count()),
                        'contacts' => (is_null($this->getRelationshipsByRelationshipTypeGroup('family')) ? null : \App\Models\Contact\Contact::translateForAPI($this->getRelationshipsByRelationshipTypeGroup('family'))),
                    ],
                    'friend' => [
                        'total' => (is_null($this->getRelationshipsByRelationshipTypeGroup('friend')) ? 0 : $this->getRelationshipsByRelationshipTypeGroup('friend')->count()),
                        'contacts' => (is_null($this->getRelationshipsByRelationshipTypeGroup('friend')) ? null : \App\Models\Contact\Contact::translateForAPI($this->getRelationshipsByRelationshipTypeGroup('friend'))),
                    ],
                    'work' => [
                        'total' => (is_null($this->getRelationshipsByRelationshipTypeGroup('work')) ? 0 : $this->getRelationshipsByRelationshipTypeGroup('work')->count()),
                        'contacts' => (is_null($this->getRelationshipsByRelationshipTypeGroup('work')) ? null : \App\Models\Contact\Contact::translateForAPI($this->getRelationshipsByRelationshipTypeGroup('work'))),
                    ],
                ]),
                'dates' => [
                    'birthdate' => [
                        'is_age_based' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_age_based),
                        'is_year_unknown' => (is_null($this->birthdate) ? null : (bool) $this->birthdate->is_year_unknown),
                        'date' => DateHelper::getTimestamp($this->birthdate),
                    ],
                    'deceased_date' => [
                        'is_age_based' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_age_based),
                        'is_year_unknown' => (is_null($this->deceasedDate) ? null : (bool) $this->deceasedDate->is_year_unknown),
                        'date' => DateHelper::getTimestamp($this->deceasedDate),
                    ],
                ],
                'career' => $this->when(! $this->is_partial, [
                    'job' => $this->job,
                    'company' => $this->company,
                ]),
                'avatar' => $this->when(! $this->is_partial, [
                    'url' => $this->getAvatarUrl(110),
                    'source' => $this->getAvatarSource(),
                    'default_avatar_color' => $this->default_avatar_color,
                ]),
                'food_preferencies' => $this->when(! $this->is_partial, $this->food_preferencies),
                'how_you_met' => $this->when(! $this->is_partial, [
                    'general_information' => $this->first_met_additional_info,
                    'first_met_date' => [
                        'is_age_based' => (is_null($this->firstMetDate) ? null : (bool) $this->firstMetDate->is_age_based),
                        'is_year_unknown' => (is_null($this->firstMetDate) ? null : (bool) $this->firstMetDate->is_year_unknown),
                        'date' => DateHelper::getTimestamp($this->firstMetDate),
                    ],
                    'first_met_through_contact' => new ContactShortResource($this->getIntroducer()),
                ]),
            ],
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
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }

    private function getHashId()
    {
        $hashid = '';
        if ($this->is_partial) {
            $realContact = $this->getRelatedRealContact();
            if ($realContact) {
                $hashid = $realContact->hashID();
            }
        } else {
            $hashid = $this->hashID();
        }

        return $hashid;
    }
}
