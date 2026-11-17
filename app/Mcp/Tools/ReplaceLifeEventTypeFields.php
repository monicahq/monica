<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Replace the groups of fields, the fields nobody grouped, or both, that a kind of life event asks for. Each of groups and fields that is sent replaces what was there, and each that is left out keeps what it holds. A field sent with its key keeps its identity, and a field missing from a list that is sent is no longer asked for. Life events already recorded keep what they were written with. The change is written to the account log.')]
#[IsDestructive]
class ReplaceLifeEventTypeFields extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.fields.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
        ]);

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);

        return $this->answerThroughApi($request, $lifeEventType->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $field = 'Each field: key (the key get-life-event-type-fields gives, to keep an existing field; leave it out for a new one), label, type (one of: '.implode(', ', array_column(FieldTypeEnum::cases(), 'value')).'), is_required, help_text, settings (an object of strings; max_length, min, max, decimals, max_choices, repeat_min and repeat_max are whole numbers) and choices (a list of strings).';

        return [
            'life_event_type' => $schema->string()->description('The id of the life event type, as list-life-event-types gives it.')->required(),
            'groups' => $schema->array()->items($schema->object())->nullable()->description("Groups of fields, each drawn under its name. Each group: placement_id (the id get-life-event-type-fields gives, to keep an existing group), name, and fields. {$field}"),
            'fields' => $schema->array()->items($schema->object())->nullable()->description("Fields nobody grouped, shown first. {$field}"),
        ];
    }
}
