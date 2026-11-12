<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionLocationStay as UpdatePossessionLocationStayAction;
use App\Http\Resources\PossessionLocationStayResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Correct one stay of a possession: its location, the day it arrived, the day it left, the reason or the note. What you leave out keeps its value, and what you send as null is cleared. The stays before and after it are not moved. A past stay keeps a day it left; the stay where it is now cannot be given one, move the possession instead. The change is written to the account journal.')]
class UpdatePossessionLocationStay extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.locationStays.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'stay' => ['required', 'string'],
            'location' => ['sometimes', 'string'],
            'arrived_on' => ['nullable', 'date_format:Y-m-d'],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $stay = $this->possessionLocationStay($possession, $validated['stay']);
        $date = fn (string $field): ?CarbonImmutable => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : CarbonImmutable::parse($validated[$field]))
            : $stay->{$field};
        $kept = fn (string $field): ?string => array_key_exists($field, $validated) ? $validated[$field] : $stay->{$field};

        new UpdatePossessionLocationStayAction(
            user: $this->user($request),
            stay: $stay,
            location: isset($validated['location']) ? $this->location($request, $validated['location']) : $stay->location,
            arrivedOn: $date('arrived_on'),
            leftOn: $date('left_on'),
            reason: $kept('reason'),
            note: $kept('note'),
        )->execute();

        return Response::json(new PossessionLocationStayResource($stay->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'stay' => $schema->string()->description('The identifier of the stay, as list-possession-location-stays gives it.')->required(),
            'location' => $schema->string()->description('The identifier of the location, as list-locations gives it.'),
            'arrived_on' => $schema->string()->description('The day it arrived, as YYYY-MM-DD, or null when unknown.'),
            'left_on' => $schema->string()->description('The day it left, as YYYY-MM-DD. Only for a past stay.'),
            'reason' => $schema->string()->description('Why it was there.'),
            'note' => $schema->string()->description('Anything worth knowing about the stay.'),
        ];
    }
}
