<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ImportTemplate;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A whole template, coming back in.
 *
 * The import reads the pasted text itself rather than anything the checking
 * screen concluded about it. What is checked has to be the bytes that are acted
 * on, or checking is a formality a second request walks straight past.
 *
 * A refused file comes back to the same screen with the text still in the box,
 * since the next thing somebody does with a file that will not read is look at
 * it.
 */
class TemplateImportController extends Controller
{
    public function new(Request $request): View
    {
        return view('app.settings.account.templates.import', [
            'viewModel' => new ImportFileViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.StructureFileReader::MAX_BYTES],
        ]);

        try {
            $template = new ImportTemplate(
                user: $request->user(),
                raw: $validated['file'],
            )->execute();
        } catch (StructureFileIsNotReadable $exception) {
            return redirect()->route('settings.templateImport.new')
                ->withInput()
                ->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The template is imported.'))
            ->with('status_description', __('It arrived as a draft. Nothing is offered to a record until you publish it.'));
    }
}
