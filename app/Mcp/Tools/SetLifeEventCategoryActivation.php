<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateLifeEventCategory;
use App\Actions\DeactivateLifeEventCategory;
use App\Http\Resources\LifeEventCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Offer a category of life event types when somebody records a life event, or stop offering it. Life events already recorded stay. The change is written to the account journal.')]
class SetLifeEventCategoryActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category = $this->lifeEventCategory($request, $validated['life_event_category']);

        if ((bool) $validated['is_active'] === $category->is_active) {
            return Response::json(new LifeEventCategoryResource($category)->resolve());
        }

        if ($validated['is_active']) {
            new ActivateLifeEventCategory(user: $this->user($request), lifeEventCategory: $category)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateLifeEventCategory(user: $this->user($request), lifeEventCategory: $category)->execute();
        }

        return Response::json(new LifeEventCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The identifier of the category, as list-life-event-categories gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
