<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read the fields a kind of life event asks for, in their groups, with the key that identifies each field. The fields nobody grouped come first.')]
#[IsReadOnly]
class GetLifeEventTypeFields extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.fields';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
        ]);

        $version = $this->lifeEventType($request, $validated['life_event_type'])->structure?->publishedVersion;

        return Response::json($version === null ? [] : StructureVersionSectionResource::collection($version->assembly)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The identifier of the life event type, as list-life-event-types gives it.')->required(),
        ];
    }
}
