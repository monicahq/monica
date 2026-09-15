<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ExportSection;
use App\Helpers\StructureFile;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\ShareSectionViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SectionExportController extends Controller
{
    public function index(Request $request, Template $template, StructureVersionSection $structureVersionSection): View
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_if($structureVersionSection->sectionVersion->status->isEditable(), 404);

        return view('app.settings.account.templates.section-export', [
            'viewModel' => new ShareSectionViewModel(
                user: $request->user(),
                template: $template,
                row: $structureVersionSection,
            ),
        ]);
    }

    public function show(Request $request, Template $template, StructureVersionSection $structureVersionSection): StreamedResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_if($structureVersionSection->sectionVersion->status->isEditable(), 404);

        $file = new ExportSection(
            user: $request->user(),
            template: $template,
            row: $structureVersionSection,
        )->execute();

        return response()->streamDownload(
            function () use ($file): void {
                echo StructureFile::encode($file);
            },
            StructureFile::nameOfSection($structureVersionSection),
            ['Content-Type' => 'application/json'],
        );
    }
}
