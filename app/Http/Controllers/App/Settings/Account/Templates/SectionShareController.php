<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\ViewModels\Settings\Account\Templates\ShareSectionViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionShareController extends Controller
{
    public function index(Request $request, Template $template, TemplateVersionSection $templateVersionSection): View
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);
        abort_if($templateVersionSection->sectionVersion->status->isEditable(), 404);

        return view('app.settings.account.templates.section-share', [
            'viewModel' => new ShareSectionViewModel(
                user: $request->user(),
                template: $template,
                row: $templateVersionSection,
            ),
        ]);
    }
}
