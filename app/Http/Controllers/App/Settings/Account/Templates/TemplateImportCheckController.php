<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reading a file without importing it.
 *
 * Checking has an address of its own for the same reason the export screen
 * shows the bytes before handing them over: somebody is entitled to see what a
 * file will become before it becomes it. Nothing here writes anything, so a
 * file that is checked ten times leaves exactly as much behind as one that is
 * never checked at all.
 */
class TemplateImportCheckController extends Controller
{
    public function create(Request $request): View
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.StructureFileReader::MAX_BYTES],
        ]);

        try {
            $checked = StructureFileReader::read($validated['file'], expecting: 'template');
            $refusal = null;
        } catch (StructureFileIsNotReadable $exception) {
            $checked = null;
            $refusal = $exception->getMessage();
        }

        return view('app.settings.account.templates.import', [
            'viewModel' => new ImportFileViewModel(
                user: $request->user(),
                raw: $validated['file'],
                checked: $checked,
                refusal: $refusal,
            ),
        ]);
    }
}
