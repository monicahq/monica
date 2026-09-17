<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ActivityTypes;

use App\Actions\UpdateActivityTypePosition;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityTypePositionController extends Controller
{
    public function update(Request $request, ActivityType $activityType): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateActivityTypePosition(
            user: $request->user(),
            activityType: $activityType,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.activityTypes.category', $activityType->activityCategory)
            ->with('status', __('The order is saved.'));
    }
}
