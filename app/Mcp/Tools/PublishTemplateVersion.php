<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\PublishStructureVersion;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\StructureVersionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Publish a draft of a template, so the records it describes start using it. The change is written to the account journal.')]
class PublishTemplateVersion extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.publication.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'change_note' => ['nullable', 'string', 'max:65535'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);

        try {
            new PublishStructureVersion(
                user: $this->user($request),
                structureVersion: $version,
                changeNote: $validated['change_note'] ?? null,
            )->execute();
        } catch (InvalidArgumentException|PublishedVersionIsFrozen|CannotPublishAnEmptyPage $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new StructureVersionResource($version->refresh()->load(['structure.owner', 'previousVersion']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft to publish.')->required(),
            'change_note' => $schema->string()->description('What changed. Required from the second published version on.'),
        ];
    }
}
