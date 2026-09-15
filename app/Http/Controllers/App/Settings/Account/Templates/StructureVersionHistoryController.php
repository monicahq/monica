<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\StructureVersionHistoryViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StructureVersionHistoryController extends Controller
{
    public function index(Request $request, Template $template): View
    {
        return view('app.settings.account.templates.versions', [
            'viewModel' => new StructureVersionHistoryViewModel(
                user: $request->user(),
                template: $template,
            ),
        ]);
    }
}
