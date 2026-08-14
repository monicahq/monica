<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ExportSection;
use App\Helpers\StructureFile;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\ViewModels\Settings\Account\Templates\ShareSectionViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One section as a file, read first and then downloaded. It works the way the
 * whole template does, one section deep.
 */
class SectionExportController extends Controller
{
    public function index(Request $request, Template $template, TemplateVersionSection $templateVersionSection): View
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_if($templateVersionSection->sectionVersion->status->isEditable(), 404);

        return view('app.settings.account.templates.section-export', [
            'viewModel' => new ShareSectionViewModel(
                user: $request->user(),
                template: $template,
                row: $templateVersionSection,
            ),
        ]);
    }

    public function show(Request $request, Template $template, TemplateVersionSection $templateVersionSection): StreamedResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_if($templateVersionSection->sectionVersion->status->isEditable(), 404);

        $file = new ExportSection(
            user: $request->user(),
            template: $template,
            row: $templateVersionSection,
        )->execute();

        return response()->streamDownload(
            function () use ($file): void {
                echo StructureFile::encode($file);
            },
            StructureFile::nameOfSection($templateVersionSection),
            ['Content-Type' => 'application/json'],
        );
    }
}
