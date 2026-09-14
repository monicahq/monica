<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\EntityTypes;

use App\Actions\ActivateEntityType;
use App\Actions\CreateEntityType;
use App\Actions\DeactivateEntityType;
use App\Actions\DestroyEntityType;
use App\Actions\UpdateEntityType;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel;
use App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntityTypeController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.entity-types.index', [
            'viewModel' => new EntityTypesViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.settings.account.entity-types.form', [
            'viewModel' => new EntityTypeFormViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules() + ['slug' => $this->slugRules($request)],
            [
                'slug.regex' => __('Use lowercase letters, numbers and dashes only, with no spaces and no accents.'),
                'slug.unique' => __('Another type in this account already uses that slug.'),
            ],
        );

        new CreateEntityType(
            user: $request->user(),
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'],
            colour: $validated['colour'],
            isActive: $request->boolean('is_active'),
            slug: $validated['slug'] ?? null,
        )->execute();

        return redirect()->route('settings.entityTypes.index')
            ->with('status', __('The type is created.'))
            ->with('status_description', __('It appears in every vault in this account.'));
    }

    public function edit(Request $request, EntityType $entityType): View
    {
        return view('app.settings.account.entity-types.form', [
            'viewModel' => new EntityTypeFormViewModel(
                user: $request->user(),
                entityType: $entityType,
            ),
        ]);
    }

    public function update(Request $request, EntityType $entityType): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        new UpdateEntityType(
            user: $request->user(),
            entityType: $entityType,
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'],
            colour: $validated['colour'],
        )->execute();

        $this->settleWhetherRecordsCanBeMade($request, $entityType);

        return redirect()->route('settings.entityTypes.index')
            ->with('status', __('The type is saved.'))
            ->with('status_description', __('Its slug is untouched, so old links still work.'));
    }

    public function destroy(Request $request, EntityType $entityType): RedirectResponse
    {
        try {
            new DestroyEntityType(
                user: $request->user(),
                entityType: $entityType,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.entityTypes.index')
                ->with('error', $e->getMessage())
                ->with('error_description', __('Turn it off instead, and everything filed under it keeps its place.'));
        }

        return redirect()->route('settings.entityTypes.index')
            ->with('status', __('The type is deleted.'))
            ->with('status_description', __('Nothing was filed under it.'));
    }

    /** @return array<int, mixed> */
    private function slugRules(Request $request): array
    {
        return [
            'nullable',
            'string',
            'max:'.EntityType::SLUG_LENGTH,
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('entity_types', 'slug')->where('account_id', $request->user()->account_id),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name_singular' => ['required', 'string', 'max:255'],
            'name_plural' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'icon' => ['required', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['required', 'string', Rule::in(EntityType::COLOURS)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function settleWhetherRecordsCanBeMade(Request $request, EntityType $entityType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $entityType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateEntityType(user: $request->user(), entityType: $entityType)->execute();

            return;
        }

        new DeactivateEntityType(user: $request->user(), entityType: $entityType)->execute();
    }
}
