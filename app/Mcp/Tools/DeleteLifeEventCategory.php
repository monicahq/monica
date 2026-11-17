<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a category the account created. One that came with the account, or that still holds types, is refused; move its types to another category first. The change is written to the account log.')]
#[IsDestructive]
class DeleteLifeEventCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
        ]);

        $lifeEventCategory = $this->lifeEventCategory($request, $validated['life_event_category']);

        $answer = $this->answerThroughApi($request, $lifeEventCategory->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The life event category is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The id of the category, as list-life-event-categories gives it.')->required(),
        ];
    }
}
