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

#[Description('Correct the name of a contact. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateContactName extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.name.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'prefix' => $schema->string()->nullable()->description('What comes before the name, such as Dr.'),
            'first_name' => $schema->string()->nullable()->description('Their given name.'),
            'middle_name' => $schema->string()->nullable()->description('Their middle name.'),
            'last_name' => $schema->string()->nullable()->description('Their family name.'),
            'suffix' => $schema->string()->nullable()->description('What comes after the name, such as Jr.'),
            'maiden_name' => $schema->string()->nullable()->description('Their name before marriage.'),
            'nickname' => $schema->string()->nullable()->description('What they are actually called.'),
            'name_order' => $schema->string()->nullable()->enum(array_column(NameOrderEnum::cases(), 'value'))->description('Which part of the name reads first.'),
        ];
    }
}
