<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\RelationshipTypes;

use App\Actions\CreateRelationshipCategory;
use App\Actions\DestroyRelationshipCategory;
use App\Actions\UpdateRelationshipCategory;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Exceptions\CannotDeleteARelationshipCategoryInUse;
use App\Http\Controllers\Controller;
use App\Models\RelationshipCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RelationshipCategoryController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newCategory', $this->rules());

        new CreateRelationshipCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The category is added.'))
            ->with('status_description', __('Put a type in it and it starts being useful.'));
    }

    public function update(Request $request, RelationshipCategory $relationshipCategory): RedirectResponse
    {
        $validated = $request->validateWithBag("category{$relationshipCategory->id}", $this->rules());

        try {
            new UpdateRelationshipCategory(
                user: $request->user(),
                relationshipCategory: $relationshipCategory,
                name: $validated['name'],
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory) {
            return redirect()->route('settings.relationshipTypes.index')
                ->with('error', __('The category came with your account and keeps its name.'));
        }

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The category is renamed.'))
            ->with('status_description', __('The types filed under it are untouched.'));
    }

    public function destroy(Request $request, RelationshipCategory $relationshipCategory): RedirectResponse
    {
        try {
            new DestroyRelationshipCategory(
                user: $request->user(),
                relationshipCategory: $relationshipCategory,
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory) {
            return redirect()->route('settings.relationshipTypes.index')
                ->with('error', __('The category came with your account and stays.'));
        } catch (CannotDeleteARelationshipCategoryInUse) {
            return redirect()->route('settings.relationshipTypes.index')
                ->with('error', __('The category still holds types and stays.'))
                ->with('error_description', __('Move them to another category first, and it can go.'));
        }

        return redirect()->route('settings.relationshipTypes.index')
            ->with('status', __('The category is deleted.'))
            ->with('status_description', __('It held no type.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
