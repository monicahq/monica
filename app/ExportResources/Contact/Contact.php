<?php

namespace App\ExportResources\Contact;

use App\ExportResources\Instance\SpecialDate;
use App\Services\Account\Settings\ExportResource;
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
                    'avatar_adorable_url' => $this->avatar_adorable_url,
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
                    return ['gender' => $this->gender->type];
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
            $this->mergeWhen($this->activities->count() > 0, [
                'activities' => $this->activities->mapUuid(),
            ]),
            $this->mergeWhen($this->calls->count() > 0, [
                'calls' => Call::collection($this->calls),
            ]),
            $this->mergeWhen($this->contactFields->count() > 0, [
                'contact_fields' => ContactField::collection($this->contactFields),
            ]),
            $this->mergeWhen($this->debts->count() > 0, [
                'debts' => Debt::collection($this->debts),
            ]),
            $this->mergeWhen($this->gifts->count() > 0, [
                'gifts' => Gift::collection($this->gifts),
            ]),
            $this->mergeWhen($this->photos->count() > 0, [
                'photos' => $this->photos->mapUuid(),
            ]),
            $this->mergeWhen($this->documents->count() > 0, [
                'documents' => $this->documents->mapUuid(),
            ]),
            $this->mergeWhen($this->notes->count() > 0, [
                'notes' => Note::collection($this->notes),
            ]),
            $this->mergeWhen($this->reminders->count() > 0, [
                'reminders' => Reminder::collection($this->reminders),
            ]),
            $this->mergeWhen($this->tasks->count() > 0, [
                'tasks' => Task::collection($this->tasks),
            ]),
            $this->mergeWhen($this->addresses->count() > 0, [
                'addresses' => Address::collection($this->addresses),
            ]),
            $this->mergeWhen($this->pets->count() > 0, [
                'pets' => Pet::collection($this->pets),
            ]),
            $this->mergeWhen($this->conversations->count() > 0, [
                'conversations' => Conversation::collection($this->conversations),
            ]),
            $this->mergeWhen($this->lifeEvents->count() > 0, [
                'life_events' => LifeEvent::collection($this->lifeEvents),
            ]),
            // $this->mergeWhen($this->occupations->count() > 0, [
            //     'occupations' => Occupation::collection($this->occupations),
            // ]),
        ];
    }
}
