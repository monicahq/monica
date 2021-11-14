<?php

namespace App\ExportResources\Account;

use App\ExportResources\User\User;
use App\ExportResources\User\Module;
use App\ExportResources\ExportResource;
use App\ExportResources\Contact\Contact;
use App\ExportResources\Contact\Document;
use App\ExportResources\Instance\AuditLog;
use App\ExportResources\Journal\JournalEntry;
use App\ExportResources\Contact\ContactFieldType;
use App\ExportResources\Relationship\Relationship;

class Account extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'number_of_invitations_sent',
    ];

    public function data(): ?array
    {
        return  [
            'data' => [
                User::countCollection($this->users),
                Contact::countCollection($this->allContacts),
                Relationship::countCollection($this->relationships),
                Addressbook::countCollection($this->addressBooks),
                AddressbookSubscription::countCollection($this->addressBookSubscriptions),
                Photo::countCollection($this->photos),
                Document::countCollection($this->documents),
                Activity::countCollection($this->activities),
            ],
            'properties' => [
                'journal_entries' => JournalEntry::collection($this->journalEntries()->entry()->get()),
                'modules' => Module::collection($this->modules),
                'reminder_rules' => ReminderRule::collection($this->reminderRules),
                'audit_logs' => AuditLog::collection($this->auditLogs),
            ],
            'instance' => [
                'activity_types' => ActivityType::collection($this->activityTypes),
                'activity_type_categories' => ActivityTypeCategory::collection($this->activityTypeCategories),
                'life_event_types' => LifeEventType::collection($this->lifeEventTypes),
                'life_event_categories' => LifeEventCategory::collection($this->lifeEventCategories),
                'contact_field_types' => ContactFieldType::collection($this->contactFieldTypes),
            ],
        ];
    }
}
