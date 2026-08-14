<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ExportTemplate;
use App\Helpers\StructureFile;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The file, read first and then downloaded.
 *
 * Reading has an address of its own because the promise the share screen makes
 * is that nothing about anybody's records leaves, and the only way to keep that
 * promise honestly is to show the whole file before it goes. A download that
 * cannot be read first asks to be trusted.
 *
 * `show` is where the export is actually performed and logged. Looking at the
 * screen is not exporting.
 */
class TemplateExportController extends Controller
{
    public function index(Request $request, Template $template): View
    {
        abort_if($template->published_version_id === null, 404);

        return view('app.settings.account.templates.export', [
            'viewModel' => new ShareTemplateViewModel(
                user: $request->user(),
                template: $template,
            ),
        ]);
    }

    public function show(Request $request, Template $template): StreamedResponse
    {
        abort_if($template->published_version_id === null, 404);

        $file = new ExportTemplate(
            user: $request->user(),
            template: $template,
        )->execute();

        return response()->streamDownload(
            function () use ($file): void {
                echo StructureFile::encode($file);
            },
            StructureFile::nameOfTemplate($template),
            ['Content-Type' => 'application/json'],
        );
    }
}
