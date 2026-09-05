<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\DuplicateTemplate;
use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateCopyController extends Controller
{
    public function create(Request $request, Template $template): RedirectResponse|View
    {
        $copy = new DuplicateTemplate(
            user: $request->user(),
            template: $template,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $copy)
            ->with('status', __('The template is copied.'))
            ->with('status_description', __('The copy is yours to change. Nothing has been published, so no record is using it yet.'));
    }
}
