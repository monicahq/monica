<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateLifeEventType as CreateLifeEventTypeAction;
use App\Enums\VaultColourEnum;
use App\Http\Resources\LifeEventTypeResource;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a kind of life event, such as Ran a marathon, in one of the account’s categories. Give it fields of its own with replace-life-event-type-fields. The change is written to the account journal.')]
class CreateLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ]);

        $type = new CreateLifeEventTypeAction(
            user: $this->user($request),
            lifeEventCategory: $this->lifeEventCategory($request, $validated['life_event_category_id']),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return Response::json(new LifeEventTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category_id' => $schema->string()->description('The identifier of the category, as list-life-event-categories gives it.')->required(),
            'name' => $schema->string()->description('The name of the kind of life event, such as Ran a marathon.')->required(),
            'description' => $schema->string()->description('A sentence saying what it is for.'),
            'icon' => $schema->string()->description('One of: '.implode(', ', Section::ICONS).'.'),
            'colour' => $schema->string()->description('One of: '.implode(', ', VaultColourEnum::values()).'.'),
        ];
    }
}
