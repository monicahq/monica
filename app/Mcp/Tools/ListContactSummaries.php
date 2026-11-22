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

#[Description('List every contact of one vault, a page at a time, with only its id, its name, whether it is partial and whether it is archived. People without a record of their own are included, archived contacts are not unless asked. Use it to see everybody at once, or to check whether a person already exists before adding them; use list-contacts to search or to read the columns of the list.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListContactSummaries extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contactSummaries';

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
        $default = (int) config('monica.api_default_contact_summaries_per_page');
        $maximum = (int) config('monica.api_maximum_contact_summaries_per_page');

        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'filter' => $schema->string()->enum(['partial', 'full'])->description('partial keeps only the people without a record of their own; full keeps only the contacts with one. Both when left out.'),
            'archived' => $schema->string()->enum(['excluded', 'included'])->description('included adds the archived contacts. excluded when left out.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many contacts a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
