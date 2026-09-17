<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ActivityTypes;

use App\Actions\ActivateActivityCategory;
use App\Actions\DeactivateActivityCategory;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityCategoryActivationController extends Controller
{
    public function update(Request $request, ActivityCategory $activityCategory): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            new ActivateActivityCategory(
                user: $request->user(),
                activityCategory: $activityCategory,
            )->execute();

            return redirect()->route('settings.activityTypes.category', $activityCategory)
                ->with('status', __('The category is offered again.'))
                ->with('status_description', __('Its types come back to activity recording with it.'));
        }

        new DeactivateActivityCategory(
            user: $request->user(),
            activityCategory: $activityCategory,
        )->execute();

        return redirect()->route('settings.activityTypes.category', $activityCategory)
            ->with('status', __('The category is switched off.'))
            ->with('status_description', __('The activities already recorded keep their type and read exactly as they did.'));
    }
}
