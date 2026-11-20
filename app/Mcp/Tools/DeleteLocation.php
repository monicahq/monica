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

#[Description('Delete a place of the account that holds no other place. One that holds others is refused until they are moved. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
        ]);

        $location = $this->location($request, $validated['location']);

        $answer = $this->answerThroughApi($request, $location->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The location is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The id of the place, as list-locations gives it.')->required(),
        ];
    }
}
