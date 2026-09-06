<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\PublishTemplateVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TemplateVersionPublicationController extends Controller
{
    public function update(Request $request, Template $template, TemplateVersion $templateVersion): RedirectResponse
    {
        $validated = $request->validate([
            'change_note' => ['nullable', 'string', 'max:65535'],
        ]);

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
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('Version :version is published.', ['version' => $templateVersion->version_number]))
            ->with('status_description', __('It is frozen now. Any further change starts a new draft beside it.'));
    }
}
