<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateShareController extends Controller
{
    public function index(Request $request, Template $template): View
    {
        abort_if($template->published_version_id === null, 404);

        return view('app.settings.account.templates.share', [
            'viewModel' => new ShareTemplateViewModel(
                user: $request->user(),
                template: $template,
            ),
        ]);
    }
}
