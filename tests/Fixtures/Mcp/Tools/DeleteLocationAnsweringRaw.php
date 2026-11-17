<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mcp\Tools;

use App\Mcp\Tools\MirrorsAnApiCapability;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteLocationAnsweringRaw extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.locations.destroy';

    public function handle(Request $request): Response
    {
        $location = $this->location($request, (string) $request->get('location'));

        return $this->answerThroughApi($request, $location->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->required(),
        ];
    }
}
