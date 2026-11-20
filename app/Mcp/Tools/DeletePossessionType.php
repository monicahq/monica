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

#[Description('Delete a possession type with its groups, its fields, and every possession of that type with the details it held. This cannot be undone. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeletePossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        $answer = $this->answerThroughApi($request, $possessionType->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The possession type is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The id of the possession type, as list-possession-types gives it.')->required(),
        ];
    }
}
