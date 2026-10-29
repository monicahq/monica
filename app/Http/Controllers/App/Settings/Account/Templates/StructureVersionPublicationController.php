<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\PublishStructureVersion;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StructureVersionPublicationController extends Controller
{
    public function update(Request $request, Template $template, StructureVersion $structureVersion): RedirectResponse
    {
        abort_unless($structureVersion->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'change_note' => ['nullable', 'string', 'max:65535'],
        ]);

        try {
            new PublishStructureVersion(
                user: $request->user(),
                structureVersion: $structureVersion,
                changeNote: $validated['change_note'] ?? null,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('Say what changed before publishing.'))
                ->with('error_description', __('The note is read by somebody deciding whether to move their records.'));
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has already been published.'));
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('Version :version is published.', ['version' => $structureVersion->version_number]))
            ->with('status_description', __('It cannot be changed now. Any further change starts a new draft beside it.'));
    }
}
