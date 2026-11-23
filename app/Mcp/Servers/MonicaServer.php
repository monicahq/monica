<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddAgentFeedbackNote;
use App\Mcp\Tools\AddGroupMember;
use App\Mcp\Tools\AddSupportMessage;
use App\Mcp\Tools\AddSupportNote;
use App\Mcp\Tools\AddTemplateSection;
use App\Mcp\Tools\AnswerGroupReminderOccurrence;
use App\Mcp\Tools\AnswerReminderOccurrence;
use App\Mcp\Tools\ChangeAgentFeedbackStatus;
use App\Mcp\Tools\ChangeSupportRequestStatus;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\CreateContactRelationship;
use App\Mcp\Tools\CreateContactReminder;
use App\Mcp\Tools\CreateEntity;
use App\Mcp\Tools\CreateEntityType;
use App\Mcp\Tools\CreateGroup;
use App\Mcp\Tools\CreateGroupLifeEvent;
use App\Mcp\Tools\CreateGroupNote;
use App\Mcp\Tools\CreateGroupReminder;
use App\Mcp\Tools\CreateKnowledgeFolder;
use App\Mcp\Tools\CreateKnowledgeList;
use App\Mcp\Tools\CreateKnowledgeListColumn;
use App\Mcp\Tools\CreateKnowledgeListItem;
use App\Mcp\Tools\CreateKnowledgeNote;
use App\Mcp\Tools\CreateLifeEvent;
use App\Mcp\Tools\CreateLifeEventCategory;
use App\Mcp\Tools\CreateLifeEventType;
use App\Mcp\Tools\CreateLocation;
use App\Mcp\Tools\CreatePossession;
use App\Mcp\Tools\CreatePossessionCondition;
use App\Mcp\Tools\CreatePossessionField;
use App\Mcp\Tools\CreatePossessionFieldGroup;
use App\Mcp\Tools\CreatePossessionInsuranceRecord;
use App\Mcp\Tools\CreatePossessionLoan;
use App\Mcp\Tools\CreatePossessionMaintenanceRecord;
use App\Mcp\Tools\CreatePossessionProvenanceEvent;
use App\Mcp\Tools\CreatePossessionTag;
use App\Mcp\Tools\CreatePossessionTransaction;
use App\Mcp\Tools\CreatePossessionType;
use App\Mcp\Tools\CreatePossessionValuation;
use App\Mcp\Tools\CreateRelationshipCategory;
use App\Mcp\Tools\CreateRelationshipType;
use App\Mcp\Tools\CreateSupportRequest;
use App\Mcp\Tools\CreateTemplate;
use App\Mcp\Tools\CreateTemplateDraft;
use App\Mcp\Tools\CreateTemplateField;
use App\Mcp\Tools\DeleteContactRelationship;
use App\Mcp\Tools\DeleteContactReminder;
use App\Mcp\Tools\DeleteEntityType;
use App\Mcp\Tools\DeleteGroup;
use App\Mcp\Tools\DeleteGroupNote;
use App\Mcp\Tools\DeleteGroupReminder;
use App\Mcp\Tools\DeleteKnowledgeFolder;
use App\Mcp\Tools\DeleteKnowledgeList;
use App\Mcp\Tools\DeleteKnowledgeListColumn;
use App\Mcp\Tools\DeleteKnowledgeListItem;
use App\Mcp\Tools\DeleteKnowledgeNote;
use App\Mcp\Tools\DeleteLifeEvent;
use App\Mcp\Tools\DeleteLifeEventCategory;
use App\Mcp\Tools\DeleteLifeEventType;
use App\Mcp\Tools\DeleteLocation;
use App\Mcp\Tools\DeletePossession;
use App\Mcp\Tools\DeletePossessionCondition;
use App\Mcp\Tools\DeletePossessionField;
use App\Mcp\Tools\DeletePossessionFieldGroup;
use App\Mcp\Tools\DeletePossessionInsuranceRecord;
use App\Mcp\Tools\DeletePossessionLoan;
use App\Mcp\Tools\DeletePossessionLocationStay;
use App\Mcp\Tools\DeletePossessionMaintenanceRecord;
use App\Mcp\Tools\DeletePossessionProvenanceEvent;
use App\Mcp\Tools\DeletePossessionTag;
use App\Mcp\Tools\DeletePossessionTransaction;
use App\Mcp\Tools\DeletePossessionType;
use App\Mcp\Tools\DeletePossessionValuation;
use App\Mcp\Tools\DeleteRelationshipCategory;
use App\Mcp\Tools\DeleteRelationshipType;
use App\Mcp\Tools\DeleteTemplate;
use App\Mcp\Tools\DeleteTemplateDraft;
use App\Mcp\Tools\DeleteTemplateField;
use App\Mcp\Tools\DuplicateTemplate;
use App\Mcp\Tools\ExportPossessionType;
use App\Mcp\Tools\GetAgentFeedback;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetContactMarkdown;
use App\Mcp\Tools\GetContactRelationship;
use App\Mcp\Tools\GetContactReminder;
use App\Mcp\Tools\GetEmailSent;
use App\Mcp\Tools\GetEntity;
use App\Mcp\Tools\GetEntityType;
use App\Mcp\Tools\GetGroup;
use App\Mcp\Tools\GetGroupNote;
use App\Mcp\Tools\GetGroupReminder;
use App\Mcp\Tools\GetKnowledgeList;
use App\Mcp\Tools\GetKnowledgeNote;
use App\Mcp\Tools\GetLifeEvent;
use App\Mcp\Tools\GetLifeEventCategory;
use App\Mcp\Tools\GetLifeEventType;
use App\Mcp\Tools\GetLifeEventTypeFields;
use App\Mcp\Tools\GetLocation;
use App\Mcp\Tools\GetLog;
use App\Mcp\Tools\GetMeContact;
use App\Mcp\Tools\GetPossession;
use App\Mcp\Tools\GetPossessionCondition;
use App\Mcp\Tools\GetPossessionInsuranceRecord;
use App\Mcp\Tools\GetPossessionLoan;
use App\Mcp\Tools\GetPossessionLocationStay;
use App\Mcp\Tools\GetPossessionMaintenanceRecord;
use App\Mcp\Tools\GetPossessionProvenanceEvent;
use App\Mcp\Tools\GetPossessionTag;
use App\Mcp\Tools\GetPossessionTransaction;
use App\Mcp\Tools\GetPossessionType;
use App\Mcp\Tools\GetPossessionValuation;
use App\Mcp\Tools\GetRelationshipCategory;
use App\Mcp\Tools\GetRelationshipType;
use App\Mcp\Tools\GetSupportQueueRequest;
use App\Mcp\Tools\GetSupportRequest;
use App\Mcp\Tools\GetTemplate;
use App\Mcp\Tools\GetTemplateVersion;
use App\Mcp\Tools\ImportPossessionType;
use App\Mcp\Tools\ListAgentFeedback;
use App\Mcp\Tools\ListContactCategories;
use App\Mcp\Tools\ListContactEntities;
use App\Mcp\Tools\ListContactGroups;
use App\Mcp\Tools\ListContactNameRepresentations;
use App\Mcp\Tools\ListContactRelationships;
use App\Mcp\Tools\ListContactReminderOccurrences;
use App\Mcp\Tools\ListContactReminders;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListContactSummaries;
use App\Mcp\Tools\ListCountries;
use App\Mcp\Tools\ListEmailsSent;
use App\Mcp\Tools\ListEntities;
use App\Mcp\Tools\ListEntityTypes;
use App\Mcp\Tools\ListGroupFiles;
use App\Mcp\Tools\ListGroupLifeEvents;
use App\Mcp\Tools\ListGroupMembers;
use App\Mcp\Tools\ListGroupNotes;
use App\Mcp\Tools\ListGroupReminderOccurrences;
use App\Mcp\Tools\ListGroupReminders;
use App\Mcp\Tools\ListGroups;
use App\Mcp\Tools\ListKnowledge;
use App\Mcp\Tools\ListLifeEventCategories;
use App\Mcp\Tools\ListLifeEventCategoryTypes;
use App\Mcp\Tools\ListLifeEvents;
use App\Mcp\Tools\ListLifeEventSlices;
use App\Mcp\Tools\ListLifeEventTypes;
use App\Mcp\Tools\ListLocations;
use App\Mcp\Tools\ListLogs;
use App\Mcp\Tools\ListPossessionConditions;
use App\Mcp\Tools\ListPossessionInsuranceRecords;
use App\Mcp\Tools\ListPossessionLoans;
use App\Mcp\Tools\ListPossessionLocationStays;
use App\Mcp\Tools\ListPossessionMaintenanceRecords;
use App\Mcp\Tools\ListPossessionProvenanceEvents;
use App\Mcp\Tools\ListPossessions;
use App\Mcp\Tools\ListPossessionTags;
use App\Mcp\Tools\ListPossessionTimeline;
use App\Mcp\Tools\ListPossessionTransactions;
use App\Mcp\Tools\ListPossessionTypes;
use App\Mcp\Tools\ListPossessionValuations;
use App\Mcp\Tools\ListRelationshipCategories;
use App\Mcp\Tools\ListRelationshipCategoryTypes;
use App\Mcp\Tools\ListRelationshipTypes;
use App\Mcp\Tools\ListSupportQueue;
use App\Mcp\Tools\ListSupportRequests;
use App\Mcp\Tools\ListTemplates;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListValueLists;
use App\Mcp\Tools\ListVaultReminderOccurrences;
use App\Mcp\Tools\ListVaultReminders;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\MovePossession;
use App\Mcp\Tools\PublishTemplateVersion;
use App\Mcp\Tools\RemoveGroupFile;
use App\Mcp\Tools\RemoveGroupMember;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\ReplaceLifeEventTypeFields;
use App\Mcp\Tools\RestoreGroup;
use App\Mcp\Tools\RestoreLifeEvent;
use App\Mcp\Tools\SearchKnowledge;
use App\Mcp\Tools\SearchRecords;
use App\Mcp\Tools\SendSupportReply;
use App\Mcp\Tools\SetContactFavourite;
use App\Mcp\Tools\SetContactPartial;
use App\Mcp\Tools\SetEntityTypeActivation;
use App\Mcp\Tools\SetEntityTypePosition;
use App\Mcp\Tools\SetLifeEventCategoryActivation;
use App\Mcp\Tools\SetLifeEventCategoryPosition;
use App\Mcp\Tools\SetLifeEventTypeActivation;
use App\Mcp\Tools\SetLifeEventTypePosition;
use App\Mcp\Tools\SetPossessionConditionPosition;
use App\Mcp\Tools\SetPossessionFieldGroupPosition;
use App\Mcp\Tools\SetPossessionFieldPosition;
use App\Mcp\Tools\SetPossessionPosition;
use App\Mcp\Tools\SetPossessionValues;
use App\Mcp\Tools\SetRelationshipCategoryActivation;
use App\Mcp\Tools\SetRelationshipCategoryPosition;
use App\Mcp\Tools\SetRelationshipTypeActivation;
use App\Mcp\Tools\SetRelationshipTypePosition;
use App\Mcp\Tools\SetTemplateActivation;
use App\Mcp\Tools\SetTemplateFieldPosition;
use App\Mcp\Tools\SetTemplatePosition;
use App\Mcp\Tools\SetTemplateSectionPosition;
use App\Mcp\Tools\SolveSupportRequest;
use App\Mcp\Tools\SubmitFeedback;
use App\Mcp\Tools\UpdateContactName;
use App\Mcp\Tools\UpdateContactNameRepresentations;
use App\Mcp\Tools\UpdateContactRelationship;
use App\Mcp\Tools\UpdateContactReminder;
use App\Mcp\Tools\UpdateContactSection;
use App\Mcp\Tools\UpdateEntity;
use App\Mcp\Tools\UpdateEntityType;
use App\Mcp\Tools\UpdateGroup;
use App\Mcp\Tools\UpdateGroupMember;
use App\Mcp\Tools\UpdateGroupNote;
use App\Mcp\Tools\UpdateGroupReminder;
use App\Mcp\Tools\UpdateKnowledgeFolder;
use App\Mcp\Tools\UpdateKnowledgeList;
use App\Mcp\Tools\UpdateKnowledgeListColumn;
use App\Mcp\Tools\UpdateKnowledgeListItem;
use App\Mcp\Tools\UpdateKnowledgeNote;
use App\Mcp\Tools\UpdateLifeEvent;
use App\Mcp\Tools\UpdateLifeEventCategory;
use App\Mcp\Tools\UpdateLifeEventType;
use App\Mcp\Tools\UpdateLocation;
use App\Mcp\Tools\UpdatePossession;
use App\Mcp\Tools\UpdatePossessionCondition;
use App\Mcp\Tools\UpdatePossessionField;
use App\Mcp\Tools\UpdatePossessionFieldGroup;
use App\Mcp\Tools\UpdatePossessionInsuranceRecord;
use App\Mcp\Tools\UpdatePossessionLoan;
use App\Mcp\Tools\UpdatePossessionLocationStay;
use App\Mcp\Tools\UpdatePossessionMaintenanceRecord;
use App\Mcp\Tools\UpdatePossessionProvenanceEvent;
use App\Mcp\Tools\UpdatePossessionTag;
use App\Mcp\Tools\UpdatePossessionTransaction;
use App\Mcp\Tools\UpdatePossessionType;
use App\Mcp\Tools\UpdateRelationshipCategory;
use App\Mcp\Tools\UpdateRelationshipType;
use App\Mcp\Tools\UpdateTemplate;
use App\Mcp\Tools\UpdateTemplateField;
use App\Mcp\Tools\UpdateTemplateSection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('Monica')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Monica is a personal directory. It holds contacts, grouped into vaults, described by templates the
account has shaped for itself.

Every capability here is confined to the account the presented key belongs to. A vault or a contact
outside it does not exist as far as this server is concerned, and asking for one answers not found
rather than forbidden. Reach a vault, a contact or any record by its id, the `id` every listing gives
you.

A vault, a contact and a life event each carry two links. links.web opens the page in Monica for the
person you are working for, and is the one to show them. links.self is the JSON API address, which
answers only to an API key and refuses a browser, so never hand it to the person.

Nothing here invents a rule of its own: each capability answers exactly as the equivalent JSON API
endpoint would, and a change made here is written to the account log as having arrived from an
assistant.

An update keeps every field it is not given and clears a field sent as null. A field that cannot be
empty, such as a name, refuses null. So send only what changes.

A template's sections and fields change only on a draft version. Open one with create-template-draft,
read the section and field ids from get-template-version, and use the ids a changing tool returns,
since a field can come back with a new one. Nothing reaches the records a template describes until the
draft is published with publish-template-version.

An entity type is a kind of record the account keeps besides contacts. Its records follow a template
created with create-template for that entity type. A type still holding records cannot be deleted,
only switched off with set-entity-type-activation.

A relationship type gives each of two linked contacts a role and is filed under a category. Types and
categories that came with the account, or that are in use, cannot be deleted, only switched off with
set-relationship-type-activation or set-relationship-category-activation.

A relationship links a contact to somebody else of the same vault: a partner, a parent, a child, a
colleague. Find the type with list-relationship-types and the contact with list-contacts or
search-records, and call list-contact-relationships first to avoid recording the same link twice.
The other person can be an existing contact, or somebody named only in the call with first_name,
or somebody whose name is unknown. Somebody named in the call gets no record of their own unless
creates_record is true: they exist only through the relationship, which is how to add a wife, a
child or a cousin the person mentions without filling the vault. holds_role_a says which person
holds role_a of a type that is not symmetric; for parent and child, true makes the contact the parent.

A location is a place of the account, such as a house, a room or a shelf, and it can sit inside
another. Two places can share a name, so tell them apart by their path, and find the place a new one
sits inside with list-locations before calling create-location. A location holding others cannot be
deleted until they are moved with update-location.

A possession type is a kind of thing the account owns, such as office equipment or a vehicle, with the
fields a thing of that kind carries, standalone or in named groups. Read one with get-possession-type
to find the identifiers of its groups and fields before changing them.

A possession is an object somebody owns, recorded in one vault: a bicycle, a painting, a car.
Record one with create-possession, giving it a type from list-possession-types; when no type fits
what the person described, ask them rather than picking one. Write the values of its type's fields
afterwards with set-possession-values, and how it was acquired with create-possession-transaction.

A life event type is a kind of thing that happens to a contact, filed under a category, and it can ask
for fields of its own. replace-life-event-type-fields replaces the set it is given, grouped or not, and
never changes a life event already recorded. Types and categories that came with the account, or that are in use, cannot be
deleted, only switched off with set-life-event-type-activation or set-life-event-category-activation.

A life event is something that happened on a date, with contacts of the vault who were there; the
first of them is the person it is recorded for and has to stay first. Before writing one, read its
type's fields with get-life-event-type-fields: values are keyed by field id, never by key, since a key
is only unique inside its group. A file field cannot be written. A deleted life event comes back with
restore-life-event.

The account log and the emails the account was sent are read with list-logs and list-emails-sent, a
page at a time, newest first, with the total. Read one email with get-email-sent to see what it said.

A reminder about a contact is told on its day and never ahead of it. Each date it produces waits for
the user to say whether they remembered it or forgot it, and answer-reminder-occurrence records that
answer on their word only: remembered from the date's day, forgotten from the day after. An answer is
final. A date nobody answered is not forgotten, and a notification sent or read is not remembered.
There is no pause: delete-contact-reminder is the only way to stop a reminder, and the answers stay.
list-vault-reminders lists every reminder of a vault whatever its date; list-vault-reminder-occurrences
lists only the dates falling in a window, 7 days ahead unless asked for more, so an empty answer from it
never means the vault has no reminders.

When a tool here fails, is missing or misleads you, tell the administrators of this instance with
submit-feedback, once per task, paraphrasing what the person asked and leaving names, dates and other
details from their vault out.
TEXT)]
class MonicaServer extends Server
{
    // An assistant that reads only the first page of tools/list must still see every tool.
    public int $defaultPaginationLength = 500;

    public int $maxPaginationLength = 500;

    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        GetContact::class,
        GetContactMarkdown::class,
        ListContactNameRepresentations::class,
        GetMeContact::class,
        ListLifeEventCategories::class,
        GetLifeEventCategory::class,
        ListLifeEventCategoryTypes::class,
        CreateLifeEventCategory::class,
        UpdateLifeEventCategory::class,
        SetLifeEventCategoryActivation::class,
        SetLifeEventCategoryPosition::class,
        DeleteLifeEventCategory::class,
        ListLifeEventTypes::class,
        GetLifeEventType::class,
        CreateLifeEventType::class,
        UpdateLifeEventType::class,
        SetLifeEventTypeActivation::class,
        SetLifeEventTypePosition::class,
        DeleteLifeEventType::class,
        GetLifeEventTypeFields::class,
        ReplaceLifeEventTypeFields::class,
        ListLogs::class,
        GetLog::class,
        ListEmailsSent::class,
        GetEmailSent::class,
        ListContacts::class,
        ListContactSummaries::class,
        SearchRecords::class,
        ListEntityTypes::class,
        GetEntityType::class,
        CreateEntityType::class,
        UpdateEntityType::class,
        SetEntityTypeActivation::class,
        SetEntityTypePosition::class,
        DeleteEntityType::class,
        ListEntities::class,
        GetEntity::class,
        CreateEntity::class,
        UpdateEntity::class,
        ListRelationshipCategories::class,
        GetRelationshipCategory::class,
        ListRelationshipCategoryTypes::class,
        CreateRelationshipCategory::class,
        UpdateRelationshipCategory::class,
        SetRelationshipCategoryActivation::class,
        SetRelationshipCategoryPosition::class,
        DeleteRelationshipCategory::class,
        ListRelationshipTypes::class,
        GetRelationshipType::class,
        CreateRelationshipType::class,
        UpdateRelationshipType::class,
        SetRelationshipTypeActivation::class,
        SetRelationshipTypePosition::class,
        DeleteRelationshipType::class,
        ListLocations::class,
        GetLocation::class,
        CreateLocation::class,
        UpdateLocation::class,
        DeleteLocation::class,
        ListPossessionTypes::class,
        GetPossessionType::class,
        CreatePossessionType::class,
        UpdatePossessionType::class,
        DeletePossessionType::class,
        ExportPossessionType::class,
        ImportPossessionType::class,
        CreatePossessionFieldGroup::class,
        UpdatePossessionFieldGroup::class,
        DeletePossessionFieldGroup::class,
        SetPossessionFieldGroupPosition::class,
        CreatePossessionField::class,
        UpdatePossessionField::class,
        DeletePossessionField::class,
        SetPossessionFieldPosition::class,
        ListPossessions::class,
        GetPossession::class,
        CreatePossession::class,
        UpdatePossession::class,
        DeletePossession::class,
        SetPossessionPosition::class,
        SetPossessionValues::class,
        ListPossessionTransactions::class,
        GetPossessionTransaction::class,
        CreatePossessionTransaction::class,
        UpdatePossessionTransaction::class,
        DeletePossessionTransaction::class,
        ListPossessionInsuranceRecords::class,
        GetPossessionInsuranceRecord::class,
        CreatePossessionInsuranceRecord::class,
        UpdatePossessionInsuranceRecord::class,
        DeletePossessionInsuranceRecord::class,
        ListPossessionTimeline::class,
        ListPossessionValuations::class,
        GetPossessionValuation::class,
        CreatePossessionValuation::class,
        DeletePossessionValuation::class,
        ListPossessionProvenanceEvents::class,
        GetPossessionProvenanceEvent::class,
        CreatePossessionProvenanceEvent::class,
        UpdatePossessionProvenanceEvent::class,
        DeletePossessionProvenanceEvent::class,
        ListPossessionLocationStays::class,
        GetPossessionLocationStay::class,
        MovePossession::class,
        UpdatePossessionLocationStay::class,
        DeletePossessionLocationStay::class,
        ListPossessionLoans::class,
        GetPossessionLoan::class,
        CreatePossessionLoan::class,
        UpdatePossessionLoan::class,
        DeletePossessionLoan::class,
        ListPossessionMaintenanceRecords::class,
        GetPossessionMaintenanceRecord::class,
        CreatePossessionMaintenanceRecord::class,
        UpdatePossessionMaintenanceRecord::class,
        DeletePossessionMaintenanceRecord::class,
        ListKnowledge::class,
        SearchKnowledge::class,
        CreateKnowledgeFolder::class,
        UpdateKnowledgeFolder::class,
        DeleteKnowledgeFolder::class,
        GetKnowledgeNote::class,
        CreateKnowledgeNote::class,
        UpdateKnowledgeNote::class,
        DeleteKnowledgeNote::class,
        GetKnowledgeList::class,
        CreateKnowledgeList::class,
        UpdateKnowledgeList::class,
        DeleteKnowledgeList::class,
        CreateKnowledgeListColumn::class,
        UpdateKnowledgeListColumn::class,
        DeleteKnowledgeListColumn::class,
        CreateKnowledgeListItem::class,
        UpdateKnowledgeListItem::class,
        DeleteKnowledgeListItem::class,
        ListPossessionTags::class,
        GetPossessionTag::class,
        CreatePossessionTag::class,
        UpdatePossessionTag::class,
        DeletePossessionTag::class,
        ListPossessionConditions::class,
        GetPossessionCondition::class,
        CreatePossessionCondition::class,
        UpdatePossessionCondition::class,
        SetPossessionConditionPosition::class,
        DeletePossessionCondition::class,
        ListTemplateVersions::class,
        ListTemplates::class,
        GetTemplate::class,
        ListVaults::class,
        CreateContact::class,
        UpdateContactName::class,
        UpdateContactNameRepresentations::class,
        UpdateContactSection::class,
        SetContactFavourite::class,
        SetContactPartial::class,
        ListContactReminders::class,
        GetContactReminder::class,
        CreateContactReminder::class,
        UpdateContactReminder::class,
        DeleteContactReminder::class,
        ListContactReminderOccurrences::class,
        ListVaultReminderOccurrences::class,
        ListVaultReminders::class,
        ListContactCategories::class,
        ListCountries::class,
        ListValueLists::class,
        AnswerReminderOccurrence::class,
        ListGroupReminders::class,
        GetGroupReminder::class,
        CreateGroupReminder::class,
        UpdateGroupReminder::class,
        DeleteGroupReminder::class,
        ListGroupReminderOccurrences::class,
        AnswerGroupReminderOccurrence::class,
        ListContactEntities::class,
        ListContactRelationships::class,
        GetContactRelationship::class,
        CreateContactRelationship::class,
        UpdateContactRelationship::class,
        DeleteContactRelationship::class,
        ListLifeEvents::class,
        ListLifeEventSlices::class,
        GetLifeEvent::class,
        CreateLifeEvent::class,
        UpdateLifeEvent::class,
        DeleteLifeEvent::class,
        RestoreLifeEvent::class,
        ListGroups::class,
        GetGroup::class,
        CreateGroup::class,
        UpdateGroup::class,
        DeleteGroup::class,
        RestoreGroup::class,
        ListGroupMembers::class,
        AddGroupMember::class,
        UpdateGroupMember::class,
        RemoveGroupMember::class,
        ListGroupFiles::class,
        RemoveGroupFile::class,
        ListGroupNotes::class,
        GetGroupNote::class,
        CreateGroupNote::class,
        UpdateGroupNote::class,
        DeleteGroupNote::class,
        ListGroupLifeEvents::class,
        CreateGroupLifeEvent::class,
        ListContactGroups::class,
        CreateTemplate::class,
        UpdateTemplate::class,
        DuplicateTemplate::class,
        SetTemplateActivation::class,
        SetTemplatePosition::class,
        DeleteTemplate::class,
        GetTemplateVersion::class,
        CreateTemplateDraft::class,
        PublishTemplateVersion::class,
        DeleteTemplateDraft::class,
        AddTemplateSection::class,
        UpdateTemplateSection::class,
        RemoveTemplateSection::class,
        SetTemplateSectionPosition::class,
        CreateTemplateField::class,
        UpdateTemplateField::class,
        DeleteTemplateField::class,
        SetTemplateFieldPosition::class,
        ListSupportRequests::class,
        GetSupportRequest::class,
        CreateSupportRequest::class,
        AddSupportMessage::class,
        SolveSupportRequest::class,
        ListSupportQueue::class,
        GetSupportQueueRequest::class,
        SendSupportReply::class,
        AddSupportNote::class,
        ChangeSupportRequestStatus::class,
        SubmitFeedback::class,
        ListAgentFeedback::class,
        GetAgentFeedback::class,
        AddAgentFeedbackNote::class,
        ChangeAgentFeedbackStatus::class,
    ];

    #[Override]
    protected function boot(): void
    {
        $this->addMethod('tools/call', RecordedCallTool::class);
    }
}
