<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLocation as UpdateLocationAction;
use App\Helpers\LocationTree;
use App\Http\Resources\LocationResource;
use App\Rules\SingleEmoji;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a place of the account, move it inside another place or to the top level, or change its emoji or address. Everything inside it moves with it. What you leave out keeps its value; send null to clear it. The change is written to the account journal.')]
class UpdateLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.update';

    public function handle(Request $request): Response
    {
        $address = $request->validate([
            'location' => ['required', 'string'],
        ]);

        $location = $this->location($request, $address['location']);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'string'],
            'emoji' => ['sometimes', 'nullable', 'string', 'max:32', new SingleEmoji],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $parent = $location->parent;

        if (array_key_exists('parent_id', $validated)) {
            $parent = $validated['parent_id'] === null ? null : $this->location($request, $validated['parent_id']);
        }

        try {
            new UpdateLocationAction(
                user: $this->user($request),
                location: $location,
                name: $validated['name'] ?? $location->name,
                parent: $parent,
                emoji: array_key_exists('emoji', $validated) ? $validated['emoji'] : $location->emoji,
                address: array_key_exists('address', $validated) ? $validated['address'] : $location->address,
            )->execute();
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new LocationResource($location, LocationTree::forAccount($location->account_id))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The identifier of the location, as list-locations gives it.')->required(),
            'name' => $schema->string()->description('The new name.'),
            'parent_id' => $schema->string()->nullable()->description('The identifier of the place to move it inside, as list-locations gives it, or null for the top level.'),
            'emoji' => $schema->string()->nullable()->description('One emoji shown in place of the default icon, or null for none.'),
            'address' => $schema->string()->nullable()->description('The address as free text, or null to use the address of the nearest place around it.'),
        ];
    }
}
