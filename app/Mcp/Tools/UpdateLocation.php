<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a place of the account, move it inside another place or to the top level, or change its emoji or address. Everything inside it moves with it. The change is written to the account log.')]
class UpdateLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
        ]);

        $location = $this->location($request, $validated['location']);

        return $this->answerThroughApi($request, $location->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The id of the place, as list-locations gives it.')->required(),
            'name' => $schema->string()->description('The name, such as Garage.'),
            'parent_id' => $schema->string()->nullable()->description('The id of the place to move it inside, as list-locations gives it. Null moves it to the top level.'),
            'emoji' => $schema->string()->nullable()->description('One emoji shown in place of the default icon. Null shows the default icon again.'),
            'address' => $schema->string()->nullable()->description('The address as free text. Null makes the place use the address of the nearest place around it that has one.'),
        ];
    }
}
