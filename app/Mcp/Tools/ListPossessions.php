<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\PossessionStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the possessions of one vault, a page at a time, by name. The list can be narrowed by type, status, location or whether they are featured. Each possession carries its type, its location, the values of its fields and its current value. In compact form it carries only its name, status, type, location and current value.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListPossessions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions';

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
            'type' => $schema->string()->description('Only possessions of this possession type, by the id list-possession-types gives.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('Only possessions with this status.'),
            'featured' => $schema->boolean()->description('True for only the featured possessions, false for only the others.'),
            'location' => $schema->string()->description('Only possessions at this location or at a location inside it, by the id list-locations gives.'),
            'sort' => $schema->string()->enum(['name', 'position'])->description('name, the default, sorts by name. position puts the featured possessions first, in their order.'),
            'compact' => $schema->boolean()->description('When true, each possession carries only its name, status, type, location and current value. Use it to go through everything quickly, then get-possession for the full record.'),
            'page' => $schema->integer()->description('The page to read, from 1.'),
            'per_page' => $schema->integer()->description('How many possessions on a page.'),
        ];
    }
}
