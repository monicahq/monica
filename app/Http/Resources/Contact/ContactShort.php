<?php

namespace App\Http\Resources\Contact;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Contact\Contact>
 */
class ContactShort extends JsonResource
{
    use ContactBase;

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
            'complete_name' => $this->name,
            'initials' => $this->getInitials(),
            'gender' => is_null($this->gender) ? null : $this->gender->name,
            'gender_type' => is_null($this->gender) ? null : $this->gender->type,
            'is_starred' => (bool) $this->is_starred,
            'is_partial' => (bool) $this->is_partial,
            'is_active' => (bool) $this->is_active,
            'is_dead' => (bool) $this->is_dead,
            'is_me' => $this->isMe(),
            'information' => [
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
                'avatar' => [
                    'url' => $this->getAvatarUrl(),
                    'source' => $this->avatar_source,
                    'default_avatar_color' => $this->default_avatar_color,
                ],
            ],
            'url' => $this->when(! $this->is_partial, route('api.contact', $this->id)),
            'account' => [
                'id' => $this->account_id,
            ],
        ];
    }
}
