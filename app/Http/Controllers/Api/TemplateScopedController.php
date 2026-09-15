<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use Illuminate\Http\Request;

/**
 * What every endpoint inside a version needs: the template scoped to the account,
 * and the version named by its number within it. Both are on every path from here
 * down, and getting either wrong is how a record of another account is reached.
 */
abstract class TemplateScopedController extends Controller
{
    protected function findTemplate(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    protected function findVersion(Template $template, string $versionNumber): StructureVersion
    {
        return $template->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();
    }

    protected function findRow(Request $request, string $template, string $version, string $id): StructureVersionSection
    {
        return $this->findVersion($this->findTemplate($request, $template), $version)
            ->assembly()
            ->where('structure_version_sections.id', $id)
            ->firstOrFail();
    }
}
