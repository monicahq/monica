<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\LifeEventTypes;

use App\Actions\UpdateLifeEventCategoryPosition;
use App\Http\Controllers\Controller;
use App\Models\LifeEventCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LifeEventCategoryPositionController extends Controller
{
    public function update(Request $request, LifeEventCategory $lifeEventCategory): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateLifeEventCategoryPosition(
            user: $request->user(),
            lifeEventCategory: $lifeEventCategory,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.lifeEventTypes.category', $lifeEventCategory)
            ->with('status', __('The order is saved.'));
    }
}
