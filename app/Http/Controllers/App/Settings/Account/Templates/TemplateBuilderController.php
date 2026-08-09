<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The screen where a template is actually built: the sections it carries, the
 * fields inside them, and one field open for editing.
 *
 * Which field is open travels in the query string rather than in a session, so
 * that the screen somebody is looking at has a URL they can go back to.
 */
class TemplateBuilderController extends Controller
{
    public function index(Request $request, Template $template): View
    {
        return view('app.settings.account.templates.builder', [
            'viewModel' => new TemplateBuilderViewModel(
                user: $request->user(),
                template: $template,
                selectedFieldId: $request->has('field') ? $request->integer('field') : null,
            ),
        ]);
    }
}
