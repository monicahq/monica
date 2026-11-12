<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\LocaleCollation;
use App\Http\Resources\PossessionTypeResource;
use App\Models\PossessionType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the possession types of the account, the kinds of things it owns such as office equipment or a vehicle, sorted by name. Each carries its colour, its icon, its groups and its fields in order.')]
#[IsReadOnly]
class ListPossessionTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionTypes';

    public function handle(Request $request): Response
    {
        $possessionTypes = $this->user($request)->account->possessionTypes()->with(['groups.fields', 'standaloneFields'])->get();

        return Response::json(LocaleCollation::sortByCollator($possessionTypes, 'name')
            ->map(fn (PossessionType $possessionType): array => new PossessionTypeResource($possessionType)->resolve())
            ->values()
            ->all());
    }
}
