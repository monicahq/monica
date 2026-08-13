<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The screen where a template is built: its pages, the sections on the open
 * one, the fields inside them, and one field open in the inspector.
 *
 * Which page and which field are open are part of the address, so every state
 * of this screen can be linked to and walked back through. The page reads by
 * its key rather than its uuid: the key is settled once, survives being
 * copied into the next draft, and makes `.../builder/life-events`
 * recognisable. A page that is not on this version opens the first one rather
 * than refusing.
 */
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
