<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddTemplateSection;
use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\CreateEntityType;
use App\Mcp\Tools\CreateTemplate;
use App\Mcp\Tools\CreateTemplateDraft;
use App\Mcp\Tools\CreateTemplateField;
use App\Mcp\Tools\DeleteEntityType;
use App\Mcp\Tools\DeleteTemplate;
use App\Mcp\Tools\DeleteTemplateDraft;
use App\Mcp\Tools\DeleteTemplateField;
use App\Mcp\Tools\DuplicateTemplate;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetEntityType;
use App\Mcp\Tools\GetMeContact;
use App\Mcp\Tools\GetTemplate;
use App\Mcp\Tools\GetTemplateVersion;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListEntityTypes;
use App\Mcp\Tools\ListLifeEventCategories;
use App\Mcp\Tools\ListRelationshipCategories;
use App\Mcp\Tools\ListRelationshipTypes;
use App\Mcp\Tools\ListTemplates;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\PublishTemplateVersion;
use App\Mcp\Tools\RemoveTemplateSection;
use App\Mcp\Tools\SetContactFavourite;
use App\Mcp\Tools\SetEntityTypeActivation;
use App\Mcp\Tools\SetEntityTypePosition;
use App\Mcp\Tools\SetTemplateActivation;
use App\Mcp\Tools\SetTemplateFieldPosition;
use App\Mcp\Tools\SetTemplatePosition;
use App\Mcp\Tools\SetTemplateSectionPosition;
use App\Mcp\Tools\UpdateContactName;
use App\Mcp\Tools\UpdateEntityType;
use App\Mcp\Tools\UpdateTemplate;
use App\Mcp\Tools\UpdateTemplateField;
use App\Mcp\Tools\UpdateTemplateSection;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Erica')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Erica is a personal directory. It holds contacts, grouped into vaults, described by templates the
account has shaped for itself.

Every capability here is confined to the account the presented key belongs to. A vault or a contact
outside it does not exist as far as this server is concerned, and asking for one answers not found
rather than forbidden. Reach a vault by its address, which every listing gives you.

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
TEXT)]
class EricaServer extends Server
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        GetContact::class,
        GetMeContact::class,
        ListLifeEventCategories::class,
        ListContacts::class,
        ListEntityTypes::class,
        GetEntityType::class,
        CreateEntityType::class,
        UpdateEntityType::class,
        SetEntityTypeActivation::class,
        SetEntityTypePosition::class,
        DeleteEntityType::class,
        ListRelationshipCategories::class,
        ListRelationshipTypes::class,
        ListTemplateVersions::class,
        ListTemplates::class,
        GetTemplate::class,
        ListVaults::class,
        CreateContact::class,
        UpdateContactName::class,
        SetContactFavourite::class,
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
}
