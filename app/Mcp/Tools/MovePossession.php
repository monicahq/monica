<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\MovePossession as MovePossessionAction;
use App\Http\Resources\PossessionLocationStayResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a possession to another location of the account on a given day. The stay where it is now closes that day and a new one opens there the same day. Refused for the location it is already in and for a day before its current stay began. The change is written to the account journal.')]
class MovePossession extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.locationStays.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'location' => ['required', 'string'],
            'moved_on' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $stay = new MovePossessionAction(
            user: $this->user($request),
            possession: $this->possession($this->vault($request, $validated['vault']), $validated['possession']),
            location: $this->location($request, $validated['location']),
            movedOn: CarbonImmutable::parse($validated['moved_on']),
            reason: $validated['reason'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return Response::json(new PossessionLocationStayResource($stay)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'location' => $schema->string()->description('The identifier of the location it moves to, as list-locations gives it.')->required(),
            'moved_on' => $schema->string()->description('The day it moved, as YYYY-MM-DD.')->required(),
            'reason' => $schema->string()->description('Why it moved.'),
            'note' => $schema->string()->description('Anything worth knowing about the new stay.'),
        ];
    }
}
