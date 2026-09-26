<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyStructureVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a draft of a template and every change made in it. A published version cannot be deleted. The change is written to the account journal.')]
#[IsDestructive]
class DeleteTemplateDraft extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);

        try {
            new DestroyStructureVersion(
                user: $this->user($request),
                structureVersion: $version,
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The draft is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft to delete.')->required(),
        ];
    }
}
