<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateSection;
use App\Enums\VaultColourEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\SectionIsAlreadyInTheStructureVersion;
use App\Http\Resources\StructureVersionSectionResource;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a section to a draft of a template: give name to create a new section, or section_id to place one the account already has. The change is written to the account journal.')]
class AddTemplateSection extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.templates.versions.sections.create';

    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'template' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'section_id' => [
                'required_without:name',
                'prohibits:name',
                'nullable',
                'string',
                Rule::exists('sections', 'uuid')->where('account_id', $user->account_id),
            ],
            'name' => ['required_without:section_id', 'nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS), 'prohibits:section_id'],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values()), 'prohibits:section_id'],
            'section_version_number' => ['nullable', 'integer', 'min:1', 'prohibits:name'],
            'page' => ['nullable', 'string'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'is_collapsed_by_default' => ['nullable', 'boolean'],
        ]);

        $version = $this->version($this->template($request, $validated['template']), (int) $validated['version']);
        $page = isset($validated['page']) ? $this->page($version, $validated['page']) : null;
        $name = $validated['name'] ?? null;

        if ($name !== null && ! $version->status->isEditable()) {
            return Response::error(__('This version has been published. Open a draft to change it.'));
        }

        $sectionVersion = $name === null
            ? $this->existingSectionVersion($user, $validated)
            : $this->newSectionVersion($user, $validated);

        try {
            $row = new AddSectionToStructureVersion(
                user: $user,
                structureVersion: $version,
                sectionVersion: $sectionVersion,
                titleOverride: $validated['title_override'] ?? null,
                isCollapsedByDefault: (bool) ($validated['is_collapsed_by_default'] ?? false),
                structureVersionPage: $page,
            )->execute();
        } catch (SectionIsAlreadyInTheStructureVersion|PublishedVersionIsFrozen|InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new StructureVersionSectionResource($row)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()->description('The identifier of the template.')->required(),
            'version' => $schema->integer()->description('The number of the draft, as create-template-draft gives it.')->required(),
            'name' => $schema->string()->description('The name of a new section to create. Leave out when giving section_id.'),
            'icon' => $schema->string()->description('The icon of a new section, only with name: '.implode(', ', Section::ICONS).'.'),
            'colour' => $schema->string()->description('The colour of a new section, only with name: '.implode(', ', VaultColourEnum::values()).'.'),
            'section_id' => $schema->string()->description('The identifier of a section the account already has, as get-template-version shows it on another template. Leave out when giving name.'),
            'section_version_number' => $schema->integer()->description('Which version of that section to place, only with section_id. The published one when left out.'),
            'page' => $schema->string()->description('The identifier of the page to place it on. The first page when left out.'),
            'title_override' => $schema->string()->description('What to head the section with in this template. Its own name when left out.'),
            'is_collapsed_by_default' => $schema->boolean()->description('Whether the section starts folded shut.'),
        ];
    }

    /** @param array<string, mixed> $validated */
    private function newSectionVersion(User $user, array $validated): SectionVersion
    {
        $section = new CreateSection(
            user: $user,
            name: $validated['name'],
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return $section->versions()->firstOrFail();
    }

    /** @param array<string, mixed> $validated */
    private function existingSectionVersion(User $user, array $validated): SectionVersion
    {
        $section = $user->account->sections()->where('uuid', $validated['section_id'])->firstOrFail();
        $number = $validated['section_version_number'] ?? null;

        if ($number === null) {
            return $section->publishedVersion
                ?? $section->versions()->orderByDesc('version_number')->firstOrFail();
        }

        $sectionVersion = $section->versions()->where('version_number', $number)->first();

        if ($sectionVersion === null) {
            throw $this->doesNotExist(__('That version does not exist.'));
        }

        return $sectionVersion;
    }
}
