<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\UpdateStructureVersionSectionPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateSectionPositionController extends Controller
{
    public function update(Request $request, Template $template, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        try {
            new UpdateStructureVersionSectionPosition(
                user: $request->user(),
                row: $structureVersionSection,
                position: (int) $validated['position'],
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has been published and cannot be changed.'));
        }

        return redirect()->route('settings.templateBuilder.index', [
            'template' => $template,
            'page' => $structureVersionSection->structureVersionPage->key,
        ]);
    }
}
