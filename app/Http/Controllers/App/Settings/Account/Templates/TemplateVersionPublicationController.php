<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\PublishSectionVersion;
use App\Actions\PublishTemplateVersion;
use App\Exceptions\CannotPublishWhileASectionIsStillADraft;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Freezing a draft: from here the version never changes again.
 *
 * The sections it carries are published first, in the same request. They were
 * assembled here and have no screen of their own, so asking for each by hand
 * would be asking about a distinction nobody can act on. What is being
 * accepted is spelled out on the button.
 */
class TemplateVersionPublicationController extends Controller
{
    public function update(Request $request, Template $template, TemplateVersion $templateVersion): RedirectResponse
    {
        $validated = $request->validate([
            'change_note' => ['nullable', 'string', 'max:65535'],
        ]);

        $this->publishTheSectionsItCarries($request, $templateVersion);

        try {
            new PublishTemplateVersion(
                user: $request->user(),
                templateVersion: $templateVersion,
                changeNote: $validated['change_note'] ?? null,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('Say what changed before publishing.'))
                ->with('error_description', __('The note is what somebody deciding whether to move their records reads.'));
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has already been published.'));
        } catch (CannotPublishWhileASectionIsStillADraft) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('One of the sections could not be published.'));
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('Version :version is published.', ['version' => $templateVersion->version_number]))
            ->with('status_description', __('It is frozen now. Any further change starts a new draft beside it.'));
    }

    /**
     * A version cannot be frozen while it points at a section somebody is
     * still editing. A section already published is left alone: it is frozen
     * and shared, and nothing here has any business making a version of it.
     */
    private function publishTheSectionsItCarries(Request $request, TemplateVersion $templateVersion): void
    {
        foreach ($templateVersion->assembly as $row) {
            $sectionVersion = $row->sectionVersion;

            if (! $sectionVersion->status->isEditable()) {
                continue;
            }

            new PublishSectionVersion(
                user: $request->user(),
                sectionVersion: $sectionVersion,
                changeNote: $sectionVersion->previous_version_id === null
                    ? null
                    : __('Published with the template.'),
            )->execute();
        }
    }
}
