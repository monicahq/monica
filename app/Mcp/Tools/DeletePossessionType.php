<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyPossessionType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a possession type with its groups, its fields, and every possession of that type with the details it held. This cannot be undone. The answer says how many possessions went, and the change is written to the account journal.')]
#[IsDestructive]
class DeletePossessionType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        $possessionCount = new DestroyPossessionType(
            user: $this->user($request),
            possessionType: $this->possessionType($request, $validated['possession_type']),
        )->execute();

        if ($possessionCount === 0) {
            return Response::text(__('The possession type is deleted.'));
        }

        return Response::text(trans_choice(
            'The possession type is deleted, with :count possession of that type.|The possession type is deleted, with :count possessions of that type.',
            $possessionCount,
        ));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The identifier of the possession type, as list-possession-types gives it.')->required(),
        ];
    }
}
