<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyLocation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a place of the account that holds no other place. One that holds others is refused until they are moved. The change is written to the account journal.')]
#[IsDestructive]
class DeleteLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'location' => ['required', 'string'],
        ]);

        try {
            new DestroyLocation(
                user: $this->user($request),
                location: $this->location($request, $validated['location']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The location is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('The identifier of the location, as list-locations gives it.')->required(),
        ];
    }
}
