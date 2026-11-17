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

#[Description('Delete a kind of life event the account created. One that came with the account, or that life events were recorded with, is refused; stop offering it instead. The change is written to the account log.')]
#[IsDestructive]
class DeleteLifeEventType extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEventTypes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'life_event_type' => ['required', 'string'],
        ]);

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);

        $answer = $this->answerThroughApi($request, $lifeEventType->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The life event type is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'life_event_type' => $schema->string()->description('The id of the life event type, as list-life-event-types gives it.')->required(),
        ];
    }
}
