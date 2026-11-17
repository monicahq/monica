<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a field to another place among the fields of its group, or among the standalone fields of its possession type. The change is written to the account log.')]
class SetPossessionFieldPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.position';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'field' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);
        $field = $this->possessionField($possessionType, $validated['field']);

        return $this->answerThroughApi($request, $possessionType->uuid, $field->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The id of the possession type, as list-possession-types gives it.')->required(),
            'field' => $schema->string()->description('The id of the field, as get-possession-type gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes among the fields beside it, counting from 1.')->required(),
        ];
    }
}
