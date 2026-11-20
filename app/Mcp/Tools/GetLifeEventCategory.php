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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one category of life event types: its name, position, whether it is offered, whether it came with the account and how many types it holds.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetLifeEventCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventCategories.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category' => ['required', 'string'],
        ]);

        $lifeEventCategory = $this->lifeEventCategory($request, $validated['life_event_category']);

        return $this->answerThroughApi($request, $lifeEventCategory->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category' => $schema->string()->description('The id of the category, as list-life-event-categories gives it.')->required(),
        ];
    }
}
