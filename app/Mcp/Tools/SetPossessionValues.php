<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionFieldValues;
use App\Http\Resources\PossessionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write the values a possession holds for fields of its type, keyed by the identifier of the field as get-possession-type gives it. Only the fields named change; null or an empty string removes a value. Text holds up to 2,000 characters, a number is a number, a date is written YYYY-MM-DD, yes or no is true or false, a choice is one of the field’s own choices, a rating is 1 to 5. Nothing is written unless every value fits. The change is written to the account journal.')]
class SetPossessionValues extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.values';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'values' => ['present', 'array'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        new UpdatePossessionFieldValues(
            user: $this->user($request),
            possession: $possession,
            values: $validated['values'],
        )->execute();

        return Response::json(new PossessionResource($possession->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'values' => $schema->object()->description('The values to write, keyed by the identifier of the field.')->required(),
        ];
    }
}
