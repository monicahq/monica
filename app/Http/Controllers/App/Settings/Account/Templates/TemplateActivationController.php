<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\ActivateTemplate;
use App\Actions\DeactivateTemplate;
use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Whether a template is still offered for a new record. This is the answer to
 * one somebody has finished with, and it is a different thing from deleting it:
 * every record already on it stays exactly as it is.
 */
class TemplateActivationController extends Controller
{
    public function update(Request $request, Template $template): RedirectResponse
    {
        $request->validate(['active' => ['required', 'boolean']]);

        if ($request->boolean('active')) {
            new ActivateTemplate(user: $request->user(), template: $template)->execute();

            return redirect()->route('settings.templates.index')
                ->with('status', __('The template is offered again.'));
        }

        new DeactivateTemplate(user: $request->user(), template: $template)->execute();

        return redirect()->route('settings.templates.index')
            ->with('status', __('The template is no longer offered.'))
            ->with('status_description', __('Every record already on it keeps what it has.'));
    }
}
