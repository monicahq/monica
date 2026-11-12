<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\ExportPossessionType;
use App\Helpers\PossessionTypeFile;
use App\Http\Controllers\Controller;
use App\Models\PossessionType;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeExportViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PossessionTypeExportController extends Controller
{
    public function index(Request $request, PossessionType $possessionType): View
    {
        return view('app.settings.account.possession-types.export', [
            'viewModel' => new PossessionTypeExportViewModel(
                user: $request->user(),
                possessionType: $possessionType,
            ),
        ]);
    }

    public function show(Request $request, PossessionType $possessionType): StreamedResponse
    {
        $file = new ExportPossessionType(
            user: $request->user(),
            possessionType: $possessionType,
        )->execute();

        return response()->streamDownload(
            function () use ($file): void {
                echo PossessionTypeFile::encode($file);
            },
            PossessionTypeFile::nameOf($possessionType),
            ['Content-Type' => 'application/json'],
        );
    }
}
