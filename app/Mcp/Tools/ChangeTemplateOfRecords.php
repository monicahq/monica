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

#[Description('Move every non-archived record of one template in a vault onto another template, or onto the published version of the same one. Values with no matching field are set aside, not deleted, and reminders on them pause; each record keeps a version history entry it can be reverted from. The move runs in the background: the answer is the change with its status, and get-template-change says how far it is. Preview it with preview-template-change first and show the result to the person before calling this.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class ChangeTemplateOfRecords extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.recordKinds.templateChanges.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'record_kind' => ['required', 'string'],
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $recordKind = $this->recordKind($request, $validated['record_kind']);
        $this->template($request, $validated['from']);
        $this->template($request, $validated['to']);

        return $this->answerThroughApi($request, $vault->uuid, $recordKind);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'record_kind' => $schema->string()->description('`contacts` for people, or the id of an entity type, as list-entity-types gives it.')->required(),
            'from' => $schema->string()->description('The id of the template the records leave, as get-record-statistics gives it.')->required(),
            'version' => $schema->integer()->nullable()->description('Take only the records on this version of the source template. Leave it out to take every version.'),
            'to' => $schema->string()->description('The id of the template the records move onto, at its published version, as get-record-statistics gives it.')->required(),
        ];
    }
}
