<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionTag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a possession tag. This cannot be undone. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionTag extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTags.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_tag' => ['required', 'string'],
        ]);

        new DestroyPossessionTag(
            user: $this->user($request),
            possessionTag: $this->possessionTag($request, $validated['possession_tag']),
        )->execute();

        return Response::text(__('The possession tag is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_tag' => $schema->string()->description('The identifier of the possession tag, as list-possession-tags gives it.')->required(),
        ];
    }
}
