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

#[Description('Say what moving every non-archived record of one template onto another would do, without changing anything: how many records can move, how many would have values set aside or brought back, which fields and reminders are touched, and which records another running change already holds. Use the same template for from and to to update records on older versions to the published one. Call it before change-template-of-records and tell the person what it says.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class PreviewTemplateChange extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.recordKinds.templateChangePreview.show';

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
