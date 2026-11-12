<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\PossessionConditionResource;
use App\Models\PossessionCondition;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the possession conditions of the account, the states its possessions can be in such as “new” or “broken”, in the order the account keeps them, from best to worst. Each carries its colour, its icon or none, and its position.')]
#[IsReadOnly]
class ListPossessionConditions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessionConditions';

    public function handle(Request $request): Response
    {
        $possessionConditions = $this->user($request)->account->possessionConditions()->get();

        return Response::json($possessionConditions
            ->map(fn (PossessionCondition $possessionCondition): array => new PossessionConditionResource($possessionCondition)->resolve())
            ->values()
            ->all());
    }
}
