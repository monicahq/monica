<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateStructureVersionDraft;
use App\Actions\DestroyStructureVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\StructureVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StructureVersionController extends Controller
{
    public function create(Request $request, Template $template): RedirectResponse
    {
        $version = $template->structure->publishedVersion ?? $template->versions()->first();

        if ($version === null) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('There is no version to start from.'));
        }

        new CreateStructureVersionDraft(
            user: $request->user(),
            structureVersion: $version,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('A draft is started.'))
            ->with('status_description', __('The published version is unchanged, and every record on it works as before.'));
    }

    public function destroy(Request $request, Template $template, StructureVersion $structureVersion): RedirectResponse
    {
        abort_unless($structureVersion->isReachableThrough($request->user(), $template), 404);

        try {
            new DestroyStructureVersion(
                user: $request->user(),
                structureVersion: $structureVersion,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('Only a draft can be deleted.'))
                ->with('error_description', __('A published version is retired instead, so the records on it keep working.'));
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The draft is deleted.'))
            ->with('status_description', __('Nothing that was published has changed.'));
    }
}
