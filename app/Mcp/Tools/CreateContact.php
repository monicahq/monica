<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\NameOrderEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Record a new contact in a vault. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateContact extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.create';

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
            'first_name' => $schema->string()->description('Their given name, the one part a contact cannot go without.')->required(),
            'prefix' => $schema->string()->description('What comes before the name, such as Dr.'),
            'middle_name' => $schema->string()->description('Their middle name.'),
            'last_name' => $schema->string()->description('Their family name.'),
            'suffix' => $schema->string()->description('What comes after the name, such as Jr.'),
            'maiden_name' => $schema->string()->description('Their name before marriage.'),
            'nickname' => $schema->string()->description('What they are actually called.'),
            'name_order' => $schema->string()->enum(array_column(NameOrderEnum::cases(), 'value'))->description('Which part of the name reads first. Left out, the reader decides.'),
            'is_partial' => $schema->boolean()->description('True for somebody you know of rather than somebody you have written down. A partial contact is off the list of people and out of the count of the plan until they are made full. False when left out.'),
            'is_favourite' => $schema->boolean()->description('True to put the contact at the top of the list. False when left out.'),
            'structure_version_id' => $schema->integer()->description('The id of the published version of one of your active contact templates, as list-template-versions gives it, which says what the page of the contact holds. Left out, the contact starts from the template the vault uses for new contacts.'),
        ];
    }
}
