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

#[Description('Delete a possession condition. This cannot be undone. The change is written to the account log.')]
#[IsDestructive]
class DeletePossessionCondition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_condition' => ['required', 'string'],
        ]);

        $possessionCondition = $this->possessionCondition($request, $validated['possession_condition']);

        $answer = $this->answerThroughApi($request, $possessionCondition->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The possession condition is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_condition' => $schema->string()->description('The id of the possession condition, as list-possession-conditions gives it.')->required(),
        ];
    }
}
