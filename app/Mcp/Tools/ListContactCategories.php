<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ContactGroupingEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the contact categories held by the contacts of a vault, with how many contacts hold each. The id of a category is what list-contacts takes in categories.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListContactCategories extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contactCategories';

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
            'filter' => $schema->string()->enum(ContactGroupingEnum::values())->description('Count only the contacts of one grouping, as list-contacts narrows them. Every contact when left out.'),
        ];
    }
}
