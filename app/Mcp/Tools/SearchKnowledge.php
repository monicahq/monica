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

#[Description('Find the notes and lists of a vault\'s Knowledge holding every word given, in their title, their text, their description or any cell. Case, accents and a typing mistake are ignored.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class SearchKnowledge extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.search';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'query' => $schema->string()->description('The words to find, at least two letters or digits.')->required(),
        ];
    }
}
