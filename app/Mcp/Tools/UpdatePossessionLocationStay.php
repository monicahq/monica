<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Correct one stay of a possession: its location, the day it arrived, the day it left, the reason or the note. The stays before and after it are not moved. A past stay keeps a day it left. The stay where it is now cannot be given a day it left: move the possession instead. The change is written to the account log.')]
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
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $stay = $this->possessionLocationStay($possession, $validated['stay']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $stay->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'stay' => $schema->string()->description('The id of the stay, as list-possession-location-stays gives it.')->required(),
            'location_id' => $schema->string()->description('The id of the location, as list-locations gives it.'),
            'arrived_on' => $schema->string()->nullable()->description('The day it arrived, as YYYY-MM-DD. Null when unknown.'),
            'left_on' => $schema->string()->nullable()->description('The day it left, as YYYY-MM-DD. Only for a past stay.'),
            'reason' => $schema->string()->nullable()->description('Why it was there, up to 255 characters.'),
            'note' => $schema->string()->nullable()->description('Anything worth knowing about the stay, up to 5,000 characters.'),
        ];
    }
}
