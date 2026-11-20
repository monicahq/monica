<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a kind of life event, change its description, icon or colour, or move it to another category. Whether it is offered changes with set-life-event-type-activation. The change is written to the account log.')]
class UpdateLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
            'life_event_category_id' => ['nullable', 'string'],
        ]);

        if (isset($validated['life_event_category_id'])) {
            $this->lifeEventCategory($request, $validated['life_event_category_id']);
        }

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);

        return $this->answerThroughApi($request, $lifeEventType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The id of the life event type, as list-life-event-types gives it.')->required(),
            'life_event_category_id' => $schema->string()->description('The id of the category to move it to, as list-life-event-categories gives it.'),
            'name' => $schema->string()->description('The new name.'),
            'description' => $schema->string()->nullable()->description('A sentence saying what it is for.'),
            'icon' => $schema->string()->nullable()->enum(Section::ICONS)->description('The icon shown next to it.'),
            'colour' => $schema->string()->nullable()->enum(VaultColourEnum::values())->description('The colour it is drawn in.'),
            'updates_last_in_touch' => $schema->boolean()->description('Whether its life events set Last in touch on the contacts they are recorded with. Changing it works the date out again for everybody who took part in one.'),
        ];
    }
}
