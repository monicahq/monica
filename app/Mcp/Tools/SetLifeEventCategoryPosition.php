<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLifeEventCategoryPosition;
use App\Http\Resources\LifeEventCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a category of life event types to another place in the list. The change is written to the account journal.')]
class SetLifeEventCategoryPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $category = $this->lifeEventCategory($request, $validated['life_event_category']);

        new UpdateLifeEventCategoryPosition(
            user: $this->user($request),
            lifeEventCategory: $category,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new LifeEventCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The identifier of the category, as list-life-event-categories gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the list of categories, counting from 1.')->required(),
        ];
    }
}
