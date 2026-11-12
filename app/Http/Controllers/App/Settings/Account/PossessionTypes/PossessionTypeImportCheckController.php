<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeImportViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionTypeImportCheckController extends Controller
{
    public function create(Request $request): View
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.PossessionTypeFileReader::MAX_BYTES],
        ]);

        try {
            $checked = PossessionTypeFileReader::read($validated['file']);
            $problems = [];
        } catch (PossessionTypeFileIsNotReadable $exception) {
            $checked = null;
            $problems = $exception->problems;
        }

        return view('app.settings.account.possession-types.import', [
            'viewModel' => new PossessionTypeImportViewModel(
                user: $request->user(),
                raw: $validated['file'],
                checked: $checked,
                problems: $problems,
            ),
        ]);
    }
}
