<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLifeEventCategory as UpdateLifeEventCategoryAction;
use App\Http\Resources\LifeEventCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a category of life event types. The change is written to the account journal.')]
class UpdateLifeEventCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = $this->lifeEventCategory($request, $validated['life_event_category']);

        new UpdateLifeEventCategoryAction(
            user: $this->user($request),
            lifeEventCategory: $category,
            name: $validated['name'],
        )->execute();

        return Response::json(new LifeEventCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The identifier of the category, as list-life-event-categories gives it.')->required(),
            'name' => $schema->string()->description('The new name.')->required(),
        ];
    }
}
