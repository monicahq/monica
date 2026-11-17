<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a section to a draft of a template: give name to create a new section, or section_id to place one the account already has. The change is written to the account log.')]
class AddTemplateSection extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $template = $this->template($request, $validated['template']);
        $version = $this->version($template, (int) $validated['version']);

        return $this->answerThroughApi($request, $template->uuid, (string) $version->version_number);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The id of the template, as list-templates gives it.')->required(),
            'version' => $schema->integer()->description('The number of the draft, as create-template-draft gives it.')->required(),
            'name' => $schema->string()->nullable()->description('The name of a new section to create. Leave it out when giving section_id.'),
            'icon' => $schema->string()->nullable()->enum(Section::ICONS)->description('The icon of a new section. Only with name.'),
            'colour' => $schema->string()->nullable()->enum(VaultColourEnum::values())->description('The colour of a new section. Only with name.'),
            'section_id' => $schema->string()->nullable()->description('The id of a section the account already has, as get-template-version shows it on another template. Leave it out when giving name.'),
            'section_version_number' => $schema->integer()->nullable()->description('Which version of the section named by section_id to place. Only with section_id. The published one when left out.'),
            'page_id' => $schema->string()->nullable()->description('The id of the page to place the section on, as get-template-version gives it. The first page when left out.'),
            'title_override' => $schema->string()->nullable()->description('What to head the section with in this template. Its own name when left out.'),
            'is_collapsed_by_default' => $schema->boolean()->nullable()->description('Whether the section starts folded shut.'),
        ];
    }
}
