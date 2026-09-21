<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\LifeEventTypes;

use App\Actions\CreateLifeEventCategory;
use App\Actions\DestroyLifeEventCategory;
use App\Actions\UpdateLifeEventCategory;
use App\Http\Controllers\Controller;
use App\Models\LifeEventCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LifeEventCategoryController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newCategory', $this->rules());

        $category = new CreateLifeEventCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.lifeEventTypes.category', $category)
            ->with('status', __('The category is added.'))
            ->with('status_description', __('Put a type in it and it starts being useful.'));
    }

    public function update(Request $request, LifeEventCategory $lifeEventCategory): RedirectResponse
    {
        $validated = $request->validateWithBag("category{$lifeEventCategory->id}", $this->rules());

        new UpdateLifeEventCategory(
            user: $request->user(),
            lifeEventCategory: $lifeEventCategory,
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventCategory)
            ->with('status', __('The category is renamed.'))
            ->with('status_description', __('The types filed under it are untouched.'));
    }

    public function destroy(Request $request, LifeEventCategory $lifeEventCategory): RedirectResponse
    {
        try {
            new DestroyLifeEventCategory(
                user: $request->user(),
                lifeEventCategory: $lifeEventCategory,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.lifeEventTypes.category', $lifeEventCategory)
                ->with('error', $e->getMessage())
                ->with('error_description', __('Switch it off instead, and it stops being offered without anything being lost.'));
        }

        return redirect()->route('settings.lifeEventTypes.index')
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
