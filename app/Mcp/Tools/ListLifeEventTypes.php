<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\LifeEventTypeResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the kinds of life event the account can record, such as Moved house or Started a job, grouped by category.')]
#[IsReadOnly]
class ListLifeEventTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes';

    public function handle(Request $request): Response
    {
        $types = $this->user($request)->account->lifeEventTypes()
            ->join('life_event_categories', 'life_event_categories.id', '=', 'life_event_types.life_event_category_id')
            ->select('life_event_types.*')
            ->with('lifeEventCategory')
            ->orderBy('life_event_categories.position')
            ->orderBy('life_event_types.position')
            ->orderBy('life_event_types.id')
            ->get();

        return Response::json(LifeEventTypeResource::collection($types)->resolve());
    }
}
