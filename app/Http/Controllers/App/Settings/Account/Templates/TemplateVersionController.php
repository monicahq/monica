<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateTemplateVersionDraft;
use App\Actions\DestroyTemplateVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Starting a draft of a template, and throwing one away. A published version
 * cannot be edited, so changing a published template means starting a draft
 * beside it.
 */
class TemplateVersionController extends Controller
{
    public function create(Request $request, Template $template): RedirectResponse
    {
        $version = $template->publishedVersion ?? $template->versions()->first();

        if ($version === null) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('There is no version to start from.'));
        }

        new CreateTemplateVersionDraft(
            user: $request->user(),
            templateVersion: $version,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('A draft is started.'))
            ->with('status_description', __('The published version is untouched, and every record on it goes on as before.'));
    }

    public function destroy(Request $request, Template $template, TemplateVersion $templateVersion): RedirectResponse
    {
        try {
            new DestroyTemplateVersion(
                user: $request->user(),
                templateVersion: $templateVersion,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('Only a draft can be thrown away.'))
                ->with('error_description', __('A published version is retired instead, so the records on it keep working.'));
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The draft is thrown away.'))
            ->with('status_description', __('Nothing that was published has changed.'));
    }
}
