<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\RelationshipTypes;

use App\Actions\CreateRelationshipType;
use App\Actions\DestroyRelationshipType;
use App\Actions\UpdateRelationshipType;
use App\Exceptions\CannotChangeARelationshipTypeDirection;
use App\Exceptions\CannotDeleteARelationshipTypeInUse;
use App\Exceptions\CannotDeleteASuppliedRelationshipType;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Http\Controllers\Controller;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel;
use App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypesViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelationshipTypeController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.relationship-types.index', [
            'viewModel' => new RelationshipTypesViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.settings.account.relationship-types.form', [
            'viewModel' => new RelationshipTypeFormViewModel(
                user: $request->user(),
                wantedCategory: $this->wantedCategory($request),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        try {
            new CreateRelationshipType(
                user: $request->user(),
                relationshipCategory: $this->category($request, (int) $validated['relationship_category_id']),
                name: $validated['name'],
                roleA: $validated['role_a'],
                roleB: $validated['role_b'] ?? $validated['role_a'],
                isSymmetric: $request->string('direction')->value() === 'symmetric',
                isActive: $request->boolean('is_active'),
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed) {
            return back()->withInput()->withErrors(['name' => __('Another type in this account already goes by that name.')]);
        }

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The type is added.'))
            ->with('status_description', __('It is offered the next time you write down a relationship.'));
    }

    public function edit(Request $request, RelationshipType $relationshipType): View
    {
        return view('app.settings.account.relationship-types.form', [
            'viewModel' => new RelationshipTypeFormViewModel(
                user: $request->user(),
                relationshipType: $relationshipType,
            ),
        ]);
    }

    public function update(Request $request, RelationshipType $relationshipType): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        try {
            new UpdateRelationshipType(
                user: $request->user(),
                relationshipType: $relationshipType,
                relationshipCategory: $this->category($request, (int) $validated['relationship_category_id']),
                name: $validated['name'],
                roleA: $validated['role_a'],
                roleB: $validated['role_b'] ?? $validated['role_a'],
                isSymmetric: $this->wantedDirection($request, $relationshipType),
                isActive: $request->boolean('is_active'),
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed) {
            return back()->withInput()->withErrors(['name' => __('Another type in this account already goes by that name.')]);
        } catch (CannotChangeARelationshipTypeDirection) {
            return back()->withInput()->withErrors(['direction' => __('This type keeps the direction it was written with.')]);
        }

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The type is saved.'))
            ->with('status_description', __('Everything already written with it reads in the new words.'));
    }

    public function destroy(Request $request, RelationshipType $relationshipType): RedirectResponse
    {
        try {
            new DestroyRelationshipType(
                user: $request->user(),
                relationshipType: $relationshipType,
            )->execute();
        } catch (CannotDeleteASuppliedRelationshipType) {
            return redirect()->route('settings.relationshipTypes.index')
                ->with('error', __('The type came with your account and stays.'))
                ->with('error_description', __('Stop offering it instead, and everything written with it keeps reading as it does.'));
        } catch (CannotDeleteARelationshipTypeInUse) {
            return redirect()->route('settings.relationshipTypes.index')
                ->with('error', __('Relationships are written with this type, so it stays.'))
                ->with('error_description', __('Stop offering it instead, and everything written with it keeps reading as it does.'));
        }

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The type is deleted.'))
            ->with('status_description', __('No relationship was written with it.'));
    }

    /**
     * A supplied type, and one already in use, keeps the direction it was written with, so what the
     * form sends about it is ignored rather than refused.
     */
    private function wantedDirection(Request $request, RelationshipType $relationshipType): bool
    {
        if ($relationshipType->is_system || $relationshipType->relationshipCount() > 0) {
            return $relationshipType->is_symmetric;
        }

        return $request->string('direction')->value() === 'symmetric';
    }

    private function wantedCategory(Request $request): ?RelationshipCategory
    {
        $wanted = $request->integer('category');

        if ($wanted === 0) {
            return null;
        }

        return RelationshipCategory::query()
            ->where('account_id', $request->user()->account_id)
            ->find($wanted);
    }

    private function category(Request $request, int $id): RelationshipCategory
    {
        return RelationshipCategory::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($id);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'relationship_category_id' => [
                'required',
                'integer',
                Rule::exists('relationship_categories', 'id')->where('account_id', $request->user()->account_id),
            ],
            'direction' => ['required', 'string', Rule::in(['symmetric', 'directed'])],
            'role_a' => ['required', 'string', 'max:255'],
            'role_b' => ['required_if:direction,directed', 'nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
