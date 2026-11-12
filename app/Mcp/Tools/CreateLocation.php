<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateLocation as CreateLocationAction;
use App\Helpers\LocationTree;
use App\Http\Resources\LocationResource;
use App\Rules\SingleEmoji;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a place to the account, such as a house, a room, a shelf or a box, at the top level or inside another place. Find the place it sits inside with list-locations first. The change is written to the account journal.')]
class CreateLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:32', new SingleEmoji],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $location = new CreateLocationAction(
            user: $this->user($request),
            name: $validated['name'],
            parent: isset($validated['parent_id']) ? $this->location($request, $validated['parent_id']) : null,
            emoji: $validated['emoji'] ?? null,
            address: $validated['address'] ?? null,
        )->execute();

        return Response::json(new LocationResource($location, LocationTree::forAccount($location->account_id))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the place is called, such as Garage.')->required(),
            'parent_id' => $schema->string()->description('The identifier of the place it sits inside, as list-locations gives it. Left out, it sits at the top level.'),
            'emoji' => $schema->string()->description('One emoji shown in place of the default icon, such as 🏠.'),
            'address' => $schema->string()->description('The address, as free text over several lines. Left out, the place uses the address of the nearest place around it that has one.'),
        ];
    }
}
