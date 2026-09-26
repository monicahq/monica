<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ReplaceLifeEventTypeFields as ReplaceLifeEventTypeFieldsAction;
use App\Enums\FieldTypeEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Helpers\LifeEventTypeFieldLayout;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Replace the fields a kind of life event asks for with the set sent. A field sent with its key keeps its identity, and a field left out is no longer asked for. Life events already recorded keep what they were written with. The change is written to the account journal.')]
#[IsDestructive]
class ReplaceLifeEventTypeFields extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.fields.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
            ...LifeEventTypeFieldLayout::rules(),
        ]);

        $type = $this->lifeEventType($request, $validated['life_event_type']);

        try {
            new ReplaceLifeEventTypeFieldsAction(
                user: $this->user($request),
                lifeEventType: $type,
                groups: LifeEventTypeFieldLayout::groups((array) $request->get('groups', [])),
                fields: LifeEventTypeFieldLayout::fields((array) $request->get('groups', []), (array) $request->get('fields', [])),
            )->execute();
        } catch (ModelNotFoundException|AcceptedFileKindIsNotValid $e) {
            return Response::error($e->getMessage());
        }

        $version = $type->refresh()->structure?->publishedVersion;

        return Response::json($version === null ? [] : StructureVersionSectionResource::collection($version->assembly)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $field = 'Each field: key (the key get-life-event-type-fields gives, to keep an existing field; leave it out for a new one), label, type (one of: '.implode(', ', array_column(FieldTypeEnum::cases(), 'value')).'), is_required, help_text, settings (an object of strings; max_length, min, max, decimals, max_choices, repeat_min and repeat_max are whole numbers) and choices (a list of strings).';

        return [
            'life_event_type' => $schema->string()->description('The identifier of the life event type, as list-life-event-types gives it.')->required(),
            'groups' => $schema->array()->items($schema->object())->description("Groups of fields, each drawn under its name. Each group: placement_id (the id get-life-event-type-fields gives, to keep an existing group), name, and fields. {$field}"),
            'fields' => $schema->array()->items($schema->object())->description("Fields nobody grouped, shown first. {$field}"),
        ];
    }
}
