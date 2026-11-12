<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\ImportPossessionType;
use App\Exceptions\PossessionTypeFileIsNotReadable;
use App\Helpers\PossessionTypeFileReader;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeImportViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PossessionTypeImportController extends Controller
{
    public function new(Request $request): View
    {
        return view('app.settings.account.possession-types.import', [
            'viewModel' => new PossessionTypeImportViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:'.PossessionTypeFileReader::MAX_BYTES],
        ]);

        try {
            $possessionType = new ImportPossessionType(
                user: $request->user(),
                raw: $validated['file'],
            )->execute();
        } catch (PossessionTypeFileIsNotReadable $exception) {
            return redirect()->route('settings.possessionTypeImport.new')
                ->withInput()
                ->withErrors(['file' => $exception->problems]);
        }

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('status', __('“:name” is imported.', ['name' => $possessionType->name]));
    }
}
