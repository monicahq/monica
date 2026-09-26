<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLifeEventType as UpdateLifeEventTypeAction;
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

#[Description('Rename a kind of life event, change its description, icon or colour, or move it to another category. Anything left out keeps its value, and null clears the description, icon or colour; whether it is offered changes with set-life-event-type-activation. The change is written to the account journal.')]
class UpdateLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
            'life_event_category_id' => ['sometimes', 'required', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ]);

        $type = $this->lifeEventType($request, $validated['life_event_type']);

        new UpdateLifeEventTypeAction(
            user: $this->user($request),
            lifeEventType: $type,
            lifeEventCategory: isset($validated['life_event_category_id'])
                ? $this->lifeEventCategory($request, $validated['life_event_category_id'])
                : $type->lifeEventCategory,
            name: $validated['name'] ?? $type->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $type->description,
            icon: array_key_exists('icon', $validated) ? $validated['icon'] : $type->icon,
            colour: array_key_exists('colour', $validated) ? $validated['colour'] : $type->colour,
        )->execute();

        return Response::json(new LifeEventTypeResource($type->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The identifier of the life event type, as list-life-event-types gives it.')->required(),
            'life_event_category_id' => $schema->string()->description('The identifier of the category to move it to, as list-life-event-categories gives it.'),
            'name' => $schema->string()->description('The new name.'),
            'description' => $schema->string()->description('The new description. Null clears it.'),
            'icon' => $schema->string()->description('One of: '.implode(', ', Section::ICONS).'. Null clears it.'),
            'colour' => $schema->string()->description('One of: '.implode(', ', VaultColourEnum::values()).'. Null clears it.'),
        ];
    }
}
