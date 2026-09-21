<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateContact;
use App\Mcp\Tools\GetContact;
use App\Mcp\Tools\GetMeContact;
use App\Mcp\Tools\ListContacts;
use App\Mcp\Tools\ListEntityTypes;
use App\Mcp\Tools\ListLifeEventCategories;
use App\Mcp\Tools\ListRelationshipCategories;
use App\Mcp\Tools\ListRelationshipTypes;
use App\Mcp\Tools\ListTemplates;
use App\Mcp\Tools\ListTemplateVersions;
use App\Mcp\Tools\ListVaults;
use App\Mcp\Tools\SetContactFavourite;
use App\Mcp\Tools\UpdateContactName;
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
        ListRelationshipCategories::class,
        ListRelationshipTypes::class,
        ListTemplateVersions::class,
        ListTemplates::class,
        ListVaults::class,
        CreateContact::class,
        UpdateContactName::class,
        SetContactFavourite::class,
    ];
}
