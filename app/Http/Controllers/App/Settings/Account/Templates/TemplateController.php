<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateTemplate;
use App\Actions\DestroyTemplate;
use App\Actions\UpdateTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Exceptions\CannotDeleteATemplateInUse;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Template;
use App\ViewModels\Settings\Account\Templates\TemplateFormViewModel;
use App\ViewModels\Settings\Account\Templates\TemplatesViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.templates.index', [
            'viewModel' => new TemplatesViewModel(
                user: $request->user(),
                query: (string) $request->string('query'),
                filter: (string) $request->string('filter', TemplatesViewModel::FILTER_ALL),
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.settings.account.templates.form', [
            'viewModel' => new TemplateFormViewModel(
                user: $request->user(),
            ),
        ]);
    }

    /**
     * A template is created with an empty draft, and the only thing worth doing
     * next is filling it, so this lands on the builder rather than back on the
     * list.
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules($request) + ['slug' => $this->slugRules($request)],
            [
                'slug.regex' => __('Use lowercase letters, numbers and dashes only, with no spaces and no accents.'),
                'slug.unique' => __('Another template in this account already uses that slug.'),
                'entity_type_id.required_if' => __('Say which kind of record this template describes.'),
            ],
        );

        $appliesTo = TemplateAppliesToEnum::from($validated['applies_to']);

        $template = new CreateTemplate(
            user: $request->user(),
            name: $validated['name'],
            appliesTo: $appliesTo,
            entityType: $this->entityType($request, $appliesTo),
            description: $validated['description'] ?? null,
            slug: $validated['slug'] ?? null,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The template is created.'))
            ->with('status_description', __('It starts with an empty draft. Nothing is offered to a record until you publish it.'));
    }

    public function edit(Request $request, Template $template): View
    {
        return view('app.settings.account.templates.form', [
            'viewModel' => new TemplateFormViewModel(
                user: $request->user(),
                template: $template,
            ),
        ]);
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        new UpdateTemplate(
            user: $request->user(),
            template: $template,
            name: $validated['name'],
            description: $validated['description'] ?? null,
        )->execute();

        return redirect()->route('settings.templates.index')
            ->with('status', __('The template is saved.'))
            ->with('status_description', __('Its slug is untouched, so old links still work.'));
    }

    /**
     * The screen only offers a deletion for a template nothing renders, so a
     * refusal here means a record landed between the page being drawn and the
     * button being pressed.
     */
    public function destroy(Request $request, Template $template): RedirectResponse
    {
        try {
            new DestroyTemplate(
                user: $request->user(),
                template: $template,
            )->execute();
        } catch (CannotDeleteATemplateInUse) {
            return redirect()->route('settings.templates.index')
                ->with('error', __('The template still renders records and stays.'))
                ->with('error_description', __('Turn it off instead, and every record already on it keeps what it has.'));
        }

        return redirect()->route('settings.templates.index')
            ->with('status', __('The template is deleted.'))
            ->with('status_description', __('Nothing was rendering with it.'));
    }

    private function entityType(Request $request, TemplateAppliesToEnum $appliesTo): ?EntityType
    {
        if (! $appliesTo->needsEntityType()) {
            return null;
        }

        return EntityType::query()
            ->where('account_id', $request->user()->account_id)
            ->find($request->integer('entity_type_id'));
    }

    /**
     * The slug is only asked for while a template is being created. Once one
     * exists the field is read only, because the slug is what URLs and the API
     * were given and a link that already went out has to keep working.
     *
     * @return array<int, mixed>
     */
    private function slugRules(Request $request): array
    {
        return [
            'nullable',
            'string',
            'max:'.Template::SLUG_LENGTH,
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('templates', 'slug')->where('account_id', $request->user()->account_id),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'applies_to' => ['required', Rule::enum(TemplateAppliesToEnum::class)],
            'entity_type_id' => [
                'required_if:applies_to,'.TemplateAppliesToEnum::Entity->value,
                'nullable',
                Rule::exists('entity_types', 'id')->where('account_id', $request->user()->account_id),
            ],
        ];
    }
}
