<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\LocationTree;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the places of the account, such as a house, a room or a shelf. Each one comes before the places inside it, and carries its full path, the address it falls back on and how many places it holds.')]
#[IsReadOnly]
class ListLocations extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations';

    public function handle(Request $request): Response
    {
        $tree = LocationTree::forAccount($this->user($request)->account_id);

        return Response::json($tree->ordered()
            ->map(fn (Location $location): array => new LocationResource($location, $tree)->resolve())
            ->values()
            ->all());
    }
}
