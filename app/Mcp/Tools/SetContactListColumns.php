<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ContactListColumnSourceEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Choose the columns the people screen of a vault shows beside the name, in one call. This changes what the screen shows and nothing written on any contact. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetContactListColumns extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contactListColumns.update';

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
            'columns' => $schema->array()->items($schema->object())->description('Every column after the name, in the order the screen shows them; it replaces whatever the vault held, and an empty array puts the defaults back. Each column: source (one of: '.implode(', ', array_column(ContactListColumnSourceEnum::cases(), 'value')).'), section (the uuid of the section holding the field, required when source is field), field_key (the key of the field, required when source is field), heading (null keeps the default heading) and is_visible (true or false). An email, a phone or a birthday field is shown by its own source (email, phone, birthday), never with source field. get-contact-list-columns gives the current columns in this shape.')->required(),
        ];
    }
}
