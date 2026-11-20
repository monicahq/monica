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

#[Description('Add a place to the account, such as a house, a room, a shelf or a box, at the top level or inside another place. Find the place it sits inside with list-locations first. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateLocation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.create';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('What the place is called, such as Garage.')->required(),
            'parent_id' => $schema->string()->description('The id of the place it sits inside, as list-locations gives it. Left out, it sits at the top level.'),
            'emoji' => $schema->string()->description('One emoji shown in place of the default icon, such as 🏠.'),
            'address' => $schema->string()->description('The address, as free text over several lines. Left out, the place uses the address of the nearest place around it that has one.'),
        ];
    }
}
