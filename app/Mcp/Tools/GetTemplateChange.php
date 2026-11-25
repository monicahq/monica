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

#[Description('Say how far a change of template started with change-template-of-records is, and once it has finished, how many records moved, were skipped or failed, with the reason for each record that did not move.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetTemplateChange extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templateChanges.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'template_change' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $templateChange = $this->templateChange($vault, $validated['template_change']);

        return $this->answerThroughApi($request, $vault->uuid, $templateChange->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'template_change' => $schema->string()->description('The id of the change, as change-template-of-records gives it.')->required(),
        ];
    }
}
