<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\LifeEventTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the kinds of life event filed under one category, in the order they are offered.')]
#[IsReadOnly]
class ListLifeEventCategoryTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.types';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
        ]);

        $types = $this->lifeEventCategory($request, $validated['life_event_category'])->lifeEventTypes()
            ->with('lifeEventCategory')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(LifeEventTypeResource::collection($types)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The identifier of the category, as list-life-event-categories gives it.')->required(),
        ];
    }
}
