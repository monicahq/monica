<?php

namespace App\ExportResources\Contact;

use App\ExportResources\Account\Photo;
use App\ExportResources\ExportResource;
use App\ExportResources\Account\Activity;
use App\ExportResources\Instance\SpecialDate;
use App\Models\Contact\Reminder as ContactReminder;

class Contact extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'description',
        'is_starred',
        'is_partial',
        'is_active',
        'is_dead',
        'job',
        'company',
        'food_preferences',
        'last_talked_to',
        'last_consulted_at',
        'number_of_views',
        'stay_in_touch_frequency',
        'stay_in_touch_trigger_date',
        'vcard',
        'distant_etag',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'avatar' => [
                    'avatar_source' => $this->avatar_source,
                    'avatar_gravatar_url' => $this->avatar_gravatar_url,
                    'avatar_adorable_uuid' => $this->avatar_adorable_uuid,
                    'avatar_default_url' => $this->avatar_default_url,
                    $this->mergeWhen($this->avatarPhoto !== null, function () {
                        return ['avatar_photo' => $this->avatarPhoto->uuid];
                    }),
                    'has_avatar' => $this->has_avatar,
                    'avatar_external_url' => $this->avatar_external_url,
                    'avatar_file_name' => $this->avatar_file_name,
                    'avatar_location' => $this->avatar_location,
                    'gravatar_url' => $this->gravatar_url,
                    'default_avatar_color' => $this->default_avatar_color,
                ],
                'tags' => $this->when($this->tags->count() > 0, function () {
                    return $this->tags->map(function ($tag) {
                        return $tag->name;
                    })->toArray();
                }),
                $this->mergeWhen($this->gender !== null, function () {
                    return ['gender' => $this->gender->uuid];
                }),
                $this->mergeWhen($this->birthdate, [
                    'birthdate' => new SpecialDate($this->birthdate),
                ]),
                $this->mergeWhen($this->deceasedDate, [
                    'deceased_date' => new SpecialDate($this->deceasedDate),
                ]),
                $this->mergeWhen($this->deceased_reminder_id, function () {
                    return ['deceased_reminder' => new Reminder(ContactReminder::find($this->deceased_reminder_id))];
                }),
                $this->mergeWhen($this->firstMetDate, [
                    'first_met_date' => new SpecialDate($this->firstMetDate),
                ]),
                $this->mergeWhen($this->getIntroducer() !== null, function () {
                    return ['first_met_through' => $this->getIntroducer()->uuid];
                }),
                $this->mergeWhen($this->first_met_reminder_id !== null, function () {
                    return ['first_met_reminder' => new Reminder(ContactReminder::find($this->first_met_reminder_id))];
                }),
            ],
            'data' => [
                Call::countCollection($this->calls),
                ContactField::countCollection($this->contactFields),
                Debt::countCollection($this->debts),
                Gift::countCollection($this->gifts),
                Note::countCollection($this->notes),
                Reminder::countCollection($this->reminders),
                Task::countCollection($this->tasks),
                Address::countCollection($this->addresses),
                Pet::countCollection($this->pets),
                Conversation::countCollection($this->conversations),
                LifeEvent::countCollection($this->lifeEvents),
                Activity::uuidCollection($this->activities),
                Photo::uuidCollection($this->photos),
                Document::uuidCollection($this->documents),
                //     Occupation::collection($this->occupations),
            ],
        ];
    }
}
