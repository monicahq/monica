<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ImportSection;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionPage;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionImportController extends Controller
{
    public function new(Request $request, Template $template, StructureVersionPage $structureVersionPage): View
    {
        abort_unless($structureVersionPage->isReachableThrough($request->user(), $template), 404);
        abort_unless($structureVersionPage->structureVersion->status->isEditable(), 404);

        return view('app.settings.account.templates.import', [
            'viewModel' => new ImportFileViewModel(
                user: $request->user(),
                template: $template,
                page: $structureVersionPage,
            ),
        ]);
    }

    public function create(Request $request, Template $template, StructureVersionPage $structureVersionPage): RedirectResponse
    {
        abort_unless($structureVersionPage->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.StructureFileReader::MAX_BYTES],
        ]);

        try {
            new ImportSection(
                user: $request->user(),
                template: $template,
                page: $structureVersionPage,
                raw: $validated['file'],
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            abort(404);
        } catch (StructureFileIsNotReadable $exception) {
            return redirect()->route('settings.sectionImport.new', [$template, $structureVersionPage])
                ->withInput()
                ->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionPage->key])
            ->with('status', __('The section is imported.'))
            ->with('status_description', __('It sits at the end of this page, and nothing that was already here moved.'));
    }
}
