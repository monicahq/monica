<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ActivityTypes;

use App\Actions\CreateActivityCategory;
use App\Actions\DestroyActivityCategory;
use App\Actions\UpdateActivityCategory;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityCategoryController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newCategory', $this->rules());

        $category = new CreateActivityCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.activityTypes.category', $category)
            ->with('status', __('The category is added.'))
            ->with('status_description', __('Put a type in it and it starts being useful.'));
    }

    public function update(Request $request, ActivityCategory $activityCategory): RedirectResponse
    {
        $validated = $request->validateWithBag("category{$activityCategory->id}", $this->rules());

        new UpdateActivityCategory(
            user: $request->user(),
            activityCategory: $activityCategory,
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.activityTypes.category', $activityCategory)
            ->with('status', __('The category is renamed.'))
            ->with('status_description', __('The types filed under it are untouched.'));
    }

    public function destroy(Request $request, ActivityCategory $activityCategory): RedirectResponse
    {
        try {
            new DestroyActivityCategory(
                user: $request->user(),
                activityCategory: $activityCategory,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.activityTypes.category', $activityCategory)
                ->with('error', $e->getMessage())
                ->with('error_description', __('Switch it off instead, and it stops being offered without anything being lost.'));
        }

        return redirect()->route('settings.activityTypes.index')
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
