<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\LifeEventTypes;

use App\Actions\ActivateLifeEventCategory;
use App\Actions\DeactivateLifeEventCategory;
use App\Http\Controllers\Controller;
use App\Models\LifeEventCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LifeEventCategoryActivationController extends Controller
{
    public function update(Request $request, LifeEventCategory $lifeEventCategory): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            new ActivateLifeEventCategory(
                user: $request->user(),
                lifeEventCategory: $lifeEventCategory,
            )->execute();

            return redirect()->route('settings.lifeEventTypes.category', $lifeEventCategory)
                ->with('status', __('The category is offered again.'))
                ->with('status_description', __('Its types come back to life event recording with it.'));
        }

        new DeactivateLifeEventCategory(
            user: $request->user(),
            lifeEventCategory: $lifeEventCategory,
        )->execute();

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventCategory)
            ->with('status', __('The category is switched off.'))
            ->with('status_description', __('The life events already recorded keep their type and read exactly as they did.'));
    }
}
