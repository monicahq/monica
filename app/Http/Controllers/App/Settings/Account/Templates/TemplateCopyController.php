<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\DuplicateTemplate;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateCopyViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateCopyController extends Controller
{
    public function new(Request $request, Template $template): View
    {
        return view('app.settings.account.templates.copy', [
            'viewModel' => new TemplateCopyViewModel(
                user: $request->user(),
                template: $template,
            ),
        ]);
    }

    public function create(Request $request, Template $template): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'slug' => [
                    'nullable',
                    'string',
                    'max:'.Template::SLUG_LENGTH,
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique('templates', 'slug')->where('account_id', $request->user()->account_id),
                ],
            ],
            [
                'slug.regex' => __('Use lowercase letters, numbers and dashes only, with no spaces and no accents.'),
                'slug.unique' => __('Another template in this account already uses that slug.'),
            ],
        );

        $copy = new DuplicateTemplate(
            user: $request->user(),
            template: $template,
            name: $validated['name'],
            slug: $validated['slug'] ?? null,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $copy)
            ->with('status', __('The template is copied.'))
            ->with('status_description', __('The copy is yours to change. Nothing has been published, so no record is using it yet.'));
    }
}
