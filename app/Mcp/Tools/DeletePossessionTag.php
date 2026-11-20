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

#[Description('Delete a possession tag. This cannot be undone. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeletePossessionTag extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_tag' => ['required', 'string'],
        ]);

        $possessionTag = $this->possessionTag($request, $validated['possession_tag']);

        $answer = $this->answerThroughApi($request, $possessionTag->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The possession tag is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_tag' => $schema->string()->description('The id of the possession tag, as list-possession-tags gives it.')->required(),
        ];
    }
}
