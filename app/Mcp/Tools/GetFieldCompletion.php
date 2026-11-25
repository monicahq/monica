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

#[Description('Say, for one template of a kind of record in a vault, how many records fill in each field, against how many records are offered the field. A record counts once per field, values set aside by a change of template do not count, and partial contacts are left out.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetFieldCompletion extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.recordKinds.statistics.fields.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'record_kind' => ['required', 'string'],
            'template' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $recordKind = $this->recordKind($request, $validated['record_kind']);
        $this->template($request, $validated['template']);

        return $this->answerThroughApi($request, $vault->uuid, $recordKind);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'record_kind' => $schema->string()->description('`contacts` for people, or the id of an entity type, as list-entity-types gives it.')->required(),
            'template' => $schema->string()->description('The id of the template, as get-record-statistics gives it.')->required(),
            'version' => $schema->integer()->nullable()->description('One version number of that template. Leave it out to count every version.'),
            'order' => $schema->string()->enum(['lowest', 'form'])->nullable()->description('`lowest` puts the least filled fields first, `form` keeps the order of the form. Defaults to `lowest`.'),
        ];
    }
}
