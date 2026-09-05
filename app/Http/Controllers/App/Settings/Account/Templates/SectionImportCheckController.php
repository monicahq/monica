<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFileReader;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionPage;
use App\ViewModels\Settings\Account\Templates\ImportFileViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionImportCheckController extends Controller
{
    public function create(Request $request, Template $template, TemplateVersionPage $templateVersionPage): View
    {
        abort_unless($templateVersionPage->isReachableThrough($request->user(), $template), 404);
        abort_unless($templateVersionPage->templateVersion->status->isEditable(), 404);

        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.StructureFileReader::MAX_BYTES],
        ]);

        try {
            $checked = StructureFileReader::read($validated['file'], expecting: 'section');
            $refusal = null;
        } catch (StructureFileIsNotReadable $exception) {
            $checked = null;
            $refusal = $exception->getMessage();
        }

        return view('app.settings.account.templates.import', [
            'viewModel' => new ImportFileViewModel(
                user: $request->user(),
                template: $template,
                page: $templateVersionPage,
                raw: $validated['file'],
                checked: $checked,
                refusal: $refusal,
            ),
        ]);
    }
}
