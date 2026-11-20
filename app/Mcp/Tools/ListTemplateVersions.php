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

#[Description('List the versions of one template, so a caller can tell a draft from what is published.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListTemplateVersions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions';

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
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many versions a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
