<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateTemplateVersionPage;
use App\Actions\DestroyTemplateVersionPage;
use App\Actions\UpdateTemplateVersionPage;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The pages of one template: adding a tab, renaming one, taking one off.
 *
 * A page belongs to one version, and only a draft may be changed, so every one
 * of these acts on the version the builder has open. The refusals are the
 * action's and are caught here rather than left to become a stack trace: the
 * screen already hides what it can, and what it cannot hide it explains.
 */
class TemplatePageController extends Controller
{
    /**
     * The label is validated into a bag of its own, since the card that asks
     * for it has to reopen holding what was typed, and the builder around it
     * carries a form editing a field with a label of its own.
     */
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
            $page = new CreateTemplateVersionPage(
                user: $request->user(),
                templateVersion: $version,
                label: $validated['label'],
            )->execute();
        } catch (PageLabelIsAlreadyUsed) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This template already has a page reading exactly that.'))
                ->with('error_description', __('Two tabs reading the same leave nobody able to say which is which.'));
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key])
            ->with('status', __('The page is added.'));
    }

    public function update(Request $request, Template $template, TemplateVersionPage $templateVersionPage): RedirectResponse
    {
        abort_unless($templateVersionPage->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        try {
            new UpdateTemplateVersionPage(
                user: $request->user(),
                page: $templateVersionPage,
                label: $validated['label'],
            )->execute();
        } catch (PageLabelIsAlreadyUsed) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $templateVersionPage->key])
                ->with('error', __('This template already has a page reading exactly that.'))
                ->with('error_description', __('Two tabs reading the same leave nobody able to say which is which.'));
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $templateVersionPage->key])
            ->with('status', __('The page is renamed.'));
    }

    /**
     * The screen only offers this for a page that can go, so a refusal here
     * means something landed on the page between it being drawn and the button
     * being pressed.
     */
    public function destroy(Request $request, Template $template, TemplateVersionPage $templateVersionPage): RedirectResponse
    {
        abort_unless($templateVersionPage->isReachableThrough($request->user(), $template), 404);

        try {
            new DestroyTemplateVersionPage(
                user: $request->user(),
                page: $templateVersionPage,
            )->execute();
        } catch (CannotDeleteTheLastPage) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $templateVersionPage->key])
                ->with('error', __('This page stays.'))
                ->with('error_description', __('It is the only one this template has, and a version with no page has nothing to render.'));
        } catch (CannotDeleteAPageInUse) {
            return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $templateVersionPage->key])
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
