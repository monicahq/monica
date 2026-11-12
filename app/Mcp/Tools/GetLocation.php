<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\LocationTree;
use App\Http\Resources\LocationResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one place of the account: its name, the place it sits inside, its emoji, its address, its full path, the address it falls back on and how many places it holds.')]
#[IsReadOnly]
class GetLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
        ]);

        $location = $this->location($request, $validated['location']);
        $tree = LocationTree::forAccount($location->account_id);

        return Response::json(new LocationResource($location, $tree)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The identifier of the location, as list-locations gives it.')->required(),
        ];
    }
}
