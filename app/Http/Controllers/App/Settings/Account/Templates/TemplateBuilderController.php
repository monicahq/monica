<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateBuilderController extends Controller
{
    public function index(Request $request, Template $template, ?string $page = null, ?string $field = null): View
    {
        return view('app.settings.account.templates.builder', [
            'viewModel' => new TemplateBuilderViewModel(
                user: $request->user(),
                template: $template,
                selectedFieldId: $field === null ? null : (int) $field,
                selectedPageKey: $page,
            ),
        ]);
    }
}
