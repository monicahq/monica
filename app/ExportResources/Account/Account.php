<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;
use App\ExportResources\Contact\Contact;
use App\ExportResources\Contact\ContactFieldType;
use App\ExportResources\Relationship\Relationship;
use App\ExportResources\User\User;
use App\ExportResources\Contact\Document;

class Account extends ExportResource
{
    protected $columns = [
        'uuid',
    ];

    protected $properties = [
        'number_of_invitations_sent',
    ];

    public function data(): ?array
    {
        return  [
            'users' => User::collection($this->users),
            $this->mergeWhen($this->allContacts->count() > 0, [
                'contacts' => Contact::collection($this->allContacts),
            ]),
            $this->mergeWhen($this->relationships->count() > 0, [
                'relationships' => Relationship::collection($this->relationships),
            ]),
            $this->mergeWhen($this->addressBooks->count() > 0, [
                'addressbooks' => AddressBook::collection($this->addressBooks),
            ]),
            $this->mergeWhen($this->addressBookSubscriptions->count() > 0, [
                'addressbook_subscriptions' => AddressBookSubscription::collection($this->addressBookSubscriptions),
            ]),
            $this->mergeWhen($this->photos->count() > 0, [
                'photos' => Photo::collection($this->photos),
            ]),
            $this->mergeWhen($this->documents->count() > 0, [
                'documents' => Document::collection($this->documents),
            ]),
            'activities' => Activity::collection($this->activities),
            'activity_types' => ActivityType::collection($this->activityTypes),
            'activity_type_categories' => ActivityTypeCategory::collection($this->activityTypeCategories),
            'life_event_types' => LifeEventType::collection($this->lifeEventTypes),
            'life_event_categories' => LifeEventCategory::collection($this->lifeEventCategories),
            'contact_field_types' => ContactFieldType::collection($this->contactFieldTypes),
        ];
    }
}
