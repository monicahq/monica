<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
