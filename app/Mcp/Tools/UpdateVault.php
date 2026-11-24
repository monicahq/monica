<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Rename a vault, change what it is for or its colour, make it the default vault, choose what a new contact in it starts from, or choose which of People, Things and Knowledge its screens show. Hiding a section deletes nothing and changes no other tool. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateVault extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.vaults.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'name' => $schema->string()->description('What the vault is called, such as Dunder Mifflin.'),
            'description' => $schema->string()->nullable()->description('What the vault is for.'),
            'colour' => $schema->string()->enum(VaultColourEnum::values())->description('The colour the vault is told apart by.'),
            'is_default' => $schema->boolean()->description('True makes this the vault records land in when nothing says which. False changes nothing.'),
            'structure_version_id' => $schema->integer()->description('The published version of a contact template that every new contact in this vault starts from.'),
            'shows_people' => $schema->boolean()->description('Whether the screens of the vault show People: its contacts, entities and groups.'),
            'shows_things' => $schema->boolean()->description('Whether the screens of the vault show Things: its possessions.'),
            'shows_knowledge' => $schema->boolean()->description('Whether the screens of the vault show Knowledge: its notes and lists.'),
        ];
    }
}
