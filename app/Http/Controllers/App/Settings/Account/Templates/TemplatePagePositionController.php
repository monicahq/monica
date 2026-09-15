<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\UpdateStructureVersionPagePosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionPage;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplatePagePositionController extends Controller
{
    public function update(Request $request, Template $template, StructureVersionPage $structureVersionPage): RedirectResponse
    {
        abort_unless($structureVersionPage->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        try {
            new UpdateStructureVersionPagePosition(
                user: $request->user(),
                page: $structureVersionPage,
                position: $validated['position'],
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has been published and cannot be changed.'))
                ->with('error_description', __('Start a draft from it, and the pages of the draft are yours to arrange.'));
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
            ->with('status', __('The pages are reordered.'));
    }
}
