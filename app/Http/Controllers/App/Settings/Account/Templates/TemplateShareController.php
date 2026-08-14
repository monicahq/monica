<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\ShareTemplateViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The screen that names the two ways a template leaves an account.
 *
 * It exists rather than a button going straight to a download, because the two
 * ways are genuinely different decisions and somebody who has only ever seen
 * one of them will not know the other is there. A file involves nobody; a
 * listing involves everybody.
 *
 * Only a template with a published version can be shared. A draft has nothing
 * frozen to write down, and answering with a screen that refuses would be worse
 * than not offering the screen at all.
 */
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
