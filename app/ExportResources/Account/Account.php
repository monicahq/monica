<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;
use App\ExportResources\Contact\Contact;
use App\ExportResources\User\User;

class Account extends ExportResource
{
    protected $columns = [
        'uuid',
        'api_key',
    ];

    protected $properties = [
        'number_of_invitations_sent',
    ];

    public function data(): ?array
    {
        return  [
            'users' => User::collection($this->users),
            $this->mergeWhen($this->contacts->count() > 0, [
                'contacts' => Contact::collection($this->contacts),
            ]),
            'activities' => Activity::collection($this->activities),
            'activity_types' => ActivityType::collection($this->activityTypes),
            'activity_type_categories' => ActivityTypeCategory::collection($this->activityTypeCategories),
        ];
    }
}
