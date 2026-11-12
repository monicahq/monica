<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionField;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove a field from a possession type. This cannot be undone. The change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
            'field' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        new DestroyPossessionField(
            user: $this->user($request),
            field: $this->possessionField($possessionType, $validated['field']),
        )->execute();

        return Response::text(__('The field is removed.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
            'field' => $schema->string()->description('The identifier of the field, as get-possession-type gives it.')->required(),
        ];
    }
}
