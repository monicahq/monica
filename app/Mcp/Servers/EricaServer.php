<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddTemplateSection;
use App\Mcp\Tools\AnswerReminderOccurrence;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\CreateContactRelationship;
use App\Mcp\Tools\CreateContactReminder;
use App\Mcp\Tools\CreateEntityType;
use App\Mcp\Tools\CreateLifeEvent;
use App\Mcp\Tools\CreateLifeEventCategory;
use App\Mcp\Tools\CreateLifeEventType;
use App\Mcp\Tools\CreateRelationshipCategory;
use App\Mcp\Tools\CreateRelationshipType;
use App\Mcp\Tools\CreateTemplate;
use App\Mcp\Tools\CreateTemplateDraft;
use App\Mcp\Tools\CreateTemplateField;
use App\Mcp\Tools\DeleteContactRelationship;
use App\Mcp\Tools\DeleteContactReminder;
use App\Mcp\Tools\DeleteEntityType;
use App\Mcp\Tools\DeleteLifeEvent;
use App\Mcp\Tools\DeleteLifeEventCategory;
use App\Mcp\Tools\DeleteLifeEventType;
use App\Mcp\Tools\DeleteRelationshipCategory;
use App\Mcp\Tools\DeleteRelationshipType;
use App\Mcp\Tools\DeleteTemplate;
use App\Mcp\Tools\DeleteTemplateDraft;
use App\Mcp\Tools\DeleteTemplateField;
use App\Mcp\Tools\DuplicateTemplate;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetContactRelationship;
use App\Mcp\Tools\GetContactReminder;
use App\Mcp\Tools\GetEmailSent;
use App\Mcp\Tools\GetEntityType;
use App\Mcp\Tools\GetLifeEvent;
use App\Mcp\Tools\GetLifeEventCategory;
use App\Mcp\Tools\GetLifeEventType;
use App\Mcp\Tools\GetLifeEventTypeFields;
use App\Mcp\Tools\GetLog;
use App\Mcp\Tools\GetMeContact;
use App\Mcp\Tools\GetRelationshipCategory;
use App\Mcp\Tools\GetRelationshipType;
use App\Mcp\Tools\GetTemplate;
use App\Mcp\Tools\GetTemplateVersion;
use App\Mcp\Tools\ListContactNameRepresentations;
use App\Mcp\Tools\ListContactRelationships;
use App\Mcp\Tools\ListContactReminderOccurrences;
use App\Mcp\Tools\ListContactReminders;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListEmailsSent;
use App\Mcp\Tools\ListEntityTypes;
use App\Mcp\Tools\ListLifeEventCategories;
use App\Mcp\Tools\ListLifeEventCategoryTypes;
use App\Mcp\Tools\ListLifeEvents;
use App\Mcp\Tools\ListLifeEventTypes;
use App\Mcp\Tools\ListLogs;
use App\Mcp\Tools\ListRelationshipCategories;
use App\Mcp\Tools\ListRelationshipCategoryTypes;
use App\Mcp\Tools\ListRelationshipTypes;
use App\Mcp\Tools\ListTemplates;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\PublishTemplateVersion;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\ReplaceLifeEventTypeFields;
use App\Mcp\Tools\RestoreLifeEvent;
use App\Mcp\Tools\SearchRecords;
use App\Mcp\Tools\SetContactFavourite;
use App\Mcp\Tools\SetEntityTypeActivation;
use App\Mcp\Tools\SetEntityTypePosition;
use App\Mcp\Tools\SetLifeEventCategoryActivation;
use App\Mcp\Tools\SetLifeEventCategoryPosition;
use App\Mcp\Tools\SetLifeEventTypeActivation;
use App\Mcp\Tools\SetLifeEventTypePosition;
use App\Mcp\Tools\SetRelationshipCategoryActivation;
use App\Mcp\Tools\SetRelationshipCategoryPosition;
use App\Mcp\Tools\SetRelationshipTypeActivation;
use App\Mcp\Tools\SetRelationshipTypePosition;
use App\Mcp\Tools\SetTemplateActivation;
use App\Mcp\Tools\SetTemplateFieldPosition;
use App\Mcp\Tools\SetTemplatePosition;
use App\Mcp\Tools\SetTemplateSectionPosition;
use App\Mcp\Tools\UpdateContactName;
use App\Mcp\Tools\UpdateContactNameRepresentations;
use App\Mcp\Tools\UpdateContactRelationship;
use App\Mcp\Tools\UpdateContactReminder;
use App\Mcp\Tools\UpdateEntityType;
use App\Mcp\Tools\UpdateLifeEvent;
use App\Mcp\Tools\UpdateLifeEventCategory;
use App\Mcp\Tools\UpdateLifeEventType;
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

#[Name('Erica')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Erica is a personal directory. It holds contacts, grouped into vaults, described by templates the
account has shaped for itself.

Every capability here is confined to the account the presented key belongs to. A vault or a contact
outside it does not exist as far as this server is concerned, and asking for one answers not found
rather than forbidden. Reach a vault by its address, which every listing gives you.

A vault, a contact and a life event each carry two links. links.web opens the page in Erica for the
person you are working for, and is the one to show them. links.self is the JSON API address, which
answers only to an API key and refuses a browser, so never hand it to the person.

Nothing here invents a rule of its own: each capability answers exactly as the equivalent JSON API
endpoint would, and a change made here is recorded in the account's changelog as having arrived from
an assistant.

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

A relationship links two contacts of one vault with a relationship type, and one record holds both
sides. Find the type with list-relationship-types and both people with list-contacts or
search-records before calling create-contact-relationship, and list-contact-relationships first to
avoid recording the same link twice. holds_role_a says which person holds role_a of a type that is
not symmetric. The other person can also be somebody not written down yet, named or not.

A life event type is a kind of thing that happens to a contact, filed under a category, and it can ask
for fields of its own. replace-life-event-type-fields replaces the whole set and never changes a life
event already recorded. Types and categories that came with the account, or that are in use, cannot be
deleted, only switched off with set-life-event-type-activation or set-life-event-category-activation.

A life event is something that happened on a date, with contacts of the vault who were there; the
first of them is the person it is recorded for and has to stay first. Before writing one, read its
type's fields with get-life-event-type-fields: values are keyed by field id, never by key, since a key
is only unique inside its group. A file field cannot be written. update-life-event keeps whatever it
is not given, and a deleted life event comes back with restore-life-event.

The account's activity and the emails it was sent are read with list-logs and list-emails-sent, a
page at a time, newest first, with the total. Read one email with get-email-sent to see what it said.

A reminder about a contact is told on its day and never ahead of it. Each date it produces waits for
the user to say whether they remembered it or forgot it, and answer-reminder-occurrence records that
answer on their word only: remembered from the date's day, forgotten from the day after. An answer is
final. A date nobody answered is not forgotten, and a notification sent or read is not remembered.
There is no pause: delete-contact-reminder is the only way to stop a reminder, and the answers stay.
TEXT)]
class EricaServer extends Server
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        GetContact::class,
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
        SearchRecords::class,
        ListEntityTypes::class,
        GetEntityType::class,
        CreateEntityType::class,
        UpdateEntityType::class,
        SetEntityTypeActivation::class,
        SetEntityTypePosition::class,
        DeleteEntityType::class,
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
        ListTemplateVersions::class,
        ListTemplates::class,
        GetTemplate::class,
        ListVaults::class,
        CreateContact::class,
        UpdateContactName::class,
        UpdateContactNameRepresentations::class,
        SetContactFavourite::class,
        ListContactReminders::class,
        GetContactReminder::class,
        CreateContactReminder::class,
        UpdateContactReminder::class,
        DeleteContactReminder::class,
        ListContactReminderOccurrences::class,
        AnswerReminderOccurrence::class,
        ListContactRelationships::class,
        GetContactRelationship::class,
        CreateContactRelationship::class,
        UpdateContactRelationship::class,
        DeleteContactRelationship::class,
        ListLifeEvents::class,
        GetLifeEvent::class,
        CreateLifeEvent::class,
        UpdateLifeEvent::class,
        DeleteLifeEvent::class,
        RestoreLifeEvent::class,
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
    ];

    #[Override]
    protected function boot(): void
    {
        $this->addMethod('tools/call', RecordedCallTool::class);
    }
}
