<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ImportSection;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionImportController extends Controller
{
    public function new(Request $request, Template $template, TemplateVersionPage $templateVersionPage): View
    {
        abort_unless($templateVersionPage->isReachableThrough($request->user(), $template), 404);
        abort_unless($templateVersionPage->templateVersion->status->isEditable(), 404);

        return view('app.settings.account.templates.import', [
            'viewModel' => new ImportFileViewModel(
                user: $request->user(),
                template: $template,
                page: $templateVersionPage,
            ),
        ]);
    }

    public function create(Request $request, Template $template, TemplateVersionPage $templateVersionPage): RedirectResponse
    {
        abort_unless($templateVersionPage->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.StructureFileReader::MAX_BYTES],
        ]);

        try {
            new ImportSection(
                user: $request->user(),
                template: $template,
                page: $templateVersionPage,
                raw: $validated['file'],
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            abort(404);
        } catch (StructureFileIsNotReadable $exception) {
            return redirect()->route('settings.sectionImport.new', [$template, $templateVersionPage])
                ->withInput()
                ->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $templateVersionPage->key])
            ->with('status', __('The section is imported.'))
            ->with('status_description', __('It sits at the end of this page, and nothing that was already here moved.'));
    }
}
