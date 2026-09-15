<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateStructureVersionPage;
use App\Actions\DestroyStructureVersionPage;
use App\Actions\UpdateStructureVersionPage;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionPage;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplatePageController extends Controller
{
    public function create(Request $request, Template $template): RedirectResponse
    {
        $validated = $request->validateWithBag('newPage', [
            'label' => ['required', 'string', 'max:255'],
        ]);

        $version = $template->versions()->first();

        if ($version === null || ! $version->status->isEditable()) {
            return $this->refuse($template);
        }

        try {
            $page = new CreateStructureVersionPage(
                user: $request->user(),
                structureVersion: $version,
                label: $validated['label'],
            )->execute();
        } catch (PageLabelIsAlreadyUsed) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This template already has a page reading exactly that.'))
                ->with('error_description', __('Two tabs reading the same cannot be told apart.'));
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key])
            ->with('status', __('The page is added.'));
    }

    public function update(Request $request, Template $template, StructureVersionPage $structureVersionPage): RedirectResponse
    {
        abort_unless($structureVersionPage->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        try {
            new UpdateStructureVersionPage(
                user: $request->user(),
                page: $structureVersionPage,
                label: $validated['label'],
            )->execute();
        } catch (PageLabelIsAlreadyUsed) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
                ->with('error', __('This template already has a page reading exactly that.'))
                ->with('error_description', __('Two tabs reading the same cannot be told apart.'));
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
            ->with('status', __('The page is renamed.'));
    }

    public function destroy(Request $request, Template $template, StructureVersionPage $structureVersionPage): RedirectResponse
    {
        abort_unless($structureVersionPage->isReachableThrough($request->user(), $template), 404);

        try {
            new DestroyStructureVersionPage(
                user: $request->user(),
                page: $structureVersionPage,
            )->execute();
        } catch (CannotDeleteTheLastPage) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
                ->with('error', __('This page stays.'))
                ->with('error_description', __('It is the only one this template has, and a version with no page has nothing to render.'));
        } catch (CannotDeleteAPageInUse) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
                ->with('error', __('This page stays.'))
                ->with('error_description', __('It still holds sections. Move them to another page or take them off the template first.'));
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The page is removed.'));
    }

    private function refuse(Template $template): RedirectResponse
    {
        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('error', __('This version has been published and cannot be changed.'))
            ->with('error_description', __('Start a draft from it, and the pages of the draft are yours to arrange.'));
    }
}
