<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ActivityTypes;

use App\Actions\UpdateActivityCategoryPosition;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityCategoryPositionController extends Controller
{
    public function update(Request $request, ActivityCategory $activityCategory): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateActivityCategoryPosition(
            user: $request->user(),
            activityCategory: $activityCategory,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.activityTypes.category', $activityCategory)
            ->with('status', __('The order is saved.'));
    }
}
