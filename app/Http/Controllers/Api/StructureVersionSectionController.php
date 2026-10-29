<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateSection;
use App\Actions\RemoveSectionFromStructureVersion;
use App\Actions\UpdateStructureVersionSection;
use App\Enums\VaultColourEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\SectionIsAlreadyInTheStructureVersion;
use App\Http\Resources\StructureVersionSectionResource;
use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StructureVersionSectionController extends TemplateScopedController
{
    public function create(Request $request, string $template, string $version): JsonResponse
    {
        $structureVersion = $this->findVersion($this->findTemplate($request, $template), $version);

        $validated = $request->validate([
            'section_id' => [
                'required_without:name',
                'prohibits:name',
                'nullable',
                'string',
                'bail',
                'uuid',
                Rule::exists('sections', 'uuid')->where('account_id', $request->user()->account_id),
            ],
            'name' => ['required_without:section_id', 'nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS), 'prohibits:section_id'],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values()), 'prohibits:section_id'],
            'section_version_number' => ['nullable', 'integer', 'min:1', 'prohibits:name'],
            'page_id' => ['nullable', 'string'],
            'title_override' => ['nullable', 'string', 'max:255'],
            'is_collapsed_by_default' => ['nullable', 'boolean'],
        ]);

        $page = isset($validated['page_id'])
            ? $structureVersion->pages()->where('uuid', $validated['page_id'])->firstOrFail()
            : null;
        $name = $validated['name'] ?? null;

        if ($name !== null && ! $structureVersion->status->isEditable()) {
            throw ValidationException::withMessages(['version' => __('This version has been published. Open a draft to change it.')]);
        }

        try {
            $row = new AddSectionToStructureVersion(
                user: $request->user(),
                structureVersion: $structureVersion,
                sectionVersion: $name === null
                    ? $this->sectionVersion($request, $validated)
                    : $this->newSectionVersion($request, $validated),
                titleOverride: $validated['title_override'] ?? null,
                isCollapsedByDefault: $request->boolean('is_collapsed_by_default'),
                structureVersionPage: $page,
            )->execute();
        } catch (SectionIsAlreadyInTheStructureVersion $e) {
            throw ValidationException::withMessages(['section_id' => $e->getMessage()]);
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['section_version_number' => $e->getMessage()]);
        }

        return new StructureVersionSectionResource($row)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'title_override' => ['present', 'nullable', 'string', 'max:255'],
            'is_collapsed_by_default' => ['nullable', 'boolean'],
        ]);

        try {
            new UpdateStructureVersionSection(
                user: $request->user(),
                row: $row,
                titleOverride: $validated['title_override'],
                isCollapsedByDefault: $request->boolean('is_collapsed_by_default'),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return new StructureVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $template, string $version, string $section): Response
    {
        $row = $this->findRow($request, $template, $version, $section);

        try {
            new RemoveSectionFromStructureVersion(
                user: $request->user(),
                row: $row,
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /** @param array<string, mixed> $validated */
    private function newSectionVersion(Request $request, array $validated): SectionVersion
    {
        $section = new CreateSection(
            user: $request->user(),
            name: $validated['name'],
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return $section->versions()->firstOrFail();
    }

    /** @param array<string, mixed> $validated */
    private function sectionVersion(Request $request, array $validated): SectionVersion
    {
        $section = $request->user()->account->sections()
            ->where('uuid', $validated['section_id'])
            ->firstOrFail();

        $number = $validated['section_version_number'] ?? null;

        if ($number === null) {
            return $section->publishedVersion
                ?? $section->versions()->orderByDesc('version_number')->firstOrFail();
        }

        return $section->versions()->where('version_number', $number)->firstOrFail();
    }
}
