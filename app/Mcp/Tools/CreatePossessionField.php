<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\PossessionFieldKindEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a field to a possession type, in one of its groups or standing alone, placed last. A choice field takes its choices in order. The change is written to the account log.')]
class CreatePossessionField extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionFields.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'possession_type' => ['required', 'string'],
        ]);

        $possessionType = $this->possessionType($request, $validated['possession_type']);

        return $this->answerThroughApi($request, $possessionType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'possession_type' => $schema->string()->description('The id of the possession type, as list-possession-types gives it.')->required(),
            'group_id' => $schema->string()->description('The id of the group the field goes in, as get-possession-type gives it. Left out, the field stands alone.'),
            'name' => $schema->string()->description('What the field is called, such as Serial number.')->required(),
            'kind' => $schema->string()->enum(array_column(PossessionFieldKindEnum::cases(), 'value'))->description('What the field holds.')->required(),
            'options' => $schema->array()->items($schema->string())->description('The choices of a choice field, in order. Ignored for every other kind.'),
        ];
    }
}
