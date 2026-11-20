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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Create a kind of life event, such as Ran a marathon, in one of the account’s categories. Give it fields of its own with replace-life-event-type-fields. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_category_id' => ['required', 'string'],
        ]);

        $this->lifeEventCategory($request, $validated['life_event_category_id']);

        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_category_id' => $schema->string()->description('The id of the category, as list-life-event-categories gives it.')->required(),
            'name' => $schema->string()->description('The name of the kind of life event, such as Ran a marathon.')->required(),
            'description' => $schema->string()->nullable()->description('A sentence saying what it is for.'),
            'icon' => $schema->string()->nullable()->enum(Section::ICONS)->description('The icon shown next to it.'),
            'colour' => $schema->string()->nullable()->enum(VaultColourEnum::values())->description('The colour it is drawn in.'),
            'updates_last_in_touch' => $schema->boolean()->description('Whether its life events set Last in touch on the contacts they are recorded with. Defaults to true.'),
        ];
    }
}
