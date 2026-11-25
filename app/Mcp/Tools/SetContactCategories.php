<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Set the categories of up to 500 contacts of one vault at once. Each contact ends with exactly the categories listed for it, and an empty list removes them all. A contact whose template has no category field, or whose category is required when it is removed, is left as it was and listed with the reason. The categories are the ids list-contact-categories gives. Each change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class SetContactCategories extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.categories.update';

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
            'contacts' => $schema->array()->items($schema->object([
                'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
                'categories' => $schema->array()->items($schema->integer())->description('The ids of the categories the contact ends with. Empty removes every category.')->required(),
            ]))->description('Each contact and the categories it ends with. At most 500.')->required(),
        ];
    }
}
