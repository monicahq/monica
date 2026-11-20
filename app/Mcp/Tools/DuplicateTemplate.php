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

#[Description('Copy a template into a new one, as a starting point. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class DuplicateTemplate extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.duplicate.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);

        $template = $this->template($request, $validated['template']);

        return $this->answerThroughApi($request, $template->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'name' => $schema->string()->nullable()->description('The name of the copy. Made from the name of the original when left out.'),
            'slug' => $schema->string()->nullable()->description('The short name of the copy. Made from the name of the copy when left out.'),
        ];
    }
}
