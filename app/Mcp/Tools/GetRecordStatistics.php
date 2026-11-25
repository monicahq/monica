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

#[Description('Count the records of one kind in a vault (people, or one entity type such as Animals): how many there are, how many are archived, which templates and versions they use, and how many sit on an older version of their template. Archived records are counted apart and nowhere else.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetRecordStatistics extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.recordKinds.statistics.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'record_kind' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $recordKind = $this->recordKind($request, $validated['record_kind']);

        return $this->answerThroughApi($request, $vault->uuid, $recordKind);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'record_kind' => $schema->string()->description('`contacts` for people, or the id of an entity type, as list-entity-types gives it.')->required(),
        ];
    }
}
