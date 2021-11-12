<?php

namespace App\ExportResources\Contact;

use App\ExportResources\Account\ContactActivity;
use App\ExportResources\Account\Photo;
use App\Services\Account\Settings\ExportResource;
use App\ExportResources\Instance\SpecialDate;

class Contact extends ExportResource
{
    protected $columns = [
        'uuid',
        // 'avatar_source',
        // 'avatar_gravatar_url',
        // 'avatar_adorable_uuid',
        // 'avatar_adorable_url',
        // 'avatar_default_url',
        // 'avatar_photo_id',
        // 'has_avatar',
        // 'avatar_external_url',
        // 'avatar_file_name',
        // 'avatar_location',
        // 'gravatar_url',
        // 'default_avatar_color',
        // 'has_avatar_bool',
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
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                //'avatar' => null,
                'tags' => null,
                'gender' => $this->gender->type,
                'deceased_date' => $this->when($this->deceasedDate, new SpecialDate($this->deceasedDate)),
                'deceased_reminder' => $this->when($this->deceased_reminder_id, new Reminder(\App\Models\Contact\Reminder::find($this->deceased_reminder_id))),
            ],
            $this->mergeWhen($this->activities->count() > 0, [
                'activities' => ContactActivity::collection($this->activities),
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
                'photos' => Photo::collection($this->photos),
            ]),
            $this->mergeWhen($this->documents->count() > 0, [
                'documents' => Document::collection($this->documents),
            ]),
        ];
    }
}
