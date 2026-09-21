<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\LifeEventTypes;

use App\Actions\UpdateLifeEventTypePosition;
use App\Http\Controllers\Controller;
use App\Models\LifeEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LifeEventTypePositionController extends Controller
{
    public function update(Request $request, LifeEventType $lifeEventType): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateLifeEventTypePosition(
            user: $request->user(),
            lifeEventType: $lifeEventType,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventType->lifeEventCategory)
            ->with('status', __('The order is saved.'));
    }
}
