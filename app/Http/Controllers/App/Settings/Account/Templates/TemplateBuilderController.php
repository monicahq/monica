<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateBuilderViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The screen where a template is actually built: the pages it is read in, the
 * sections on the open one, the fields inside them, and one field open for
 * editing.
 *
 * Which page and which field are open are part of the address rather than a
 * query string on it, so that every state of this screen is a place somebody
 * can link to, come back to and press the back button through.
 *
 * The page reads by its key rather than by its uuid, which is what the key is
 * for: it is settled once, survives being copied into the next draft, and makes
 * `.../builder/life-events` a URL somebody can recognise. A page nobody named,
 * or one named that is not on this version, opens the first one rather than
 * refusing.
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
