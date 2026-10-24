<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Description('Fail the way a broken action would, with an exception nobody expected.')]
class BreakUnexpectedly extends Tool
{
    public function handle(Request $request): Response
    {
        throw new RuntimeException('The Dundies database is locked.');
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
