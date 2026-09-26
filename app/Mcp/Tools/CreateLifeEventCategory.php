<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateLifeEventCategory as CreateLifeEventCategoryAction;
use App\Http\Resources\LifeEventCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a category to file kinds of life event under, such as Sport. It is added at the end of the list. The change is written to the account journal.')]
class CreateLifeEventCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = new CreateLifeEventCategoryAction(
            user: $this->user($request),
            name: $validated['name'],
        )->execute();

        return Response::json(new LifeEventCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the category, such as Sport.')->required(),
        ];
    }
}
