<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionConditions;

use App\Actions\UpdatePossessionConditionPosition;
use App\Http\Controllers\Controller;
use App\Models\PossessionCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionConditionPositionController extends Controller
{
    public function update(Request $request, PossessionCondition $possessionCondition): RedirectResponse
    {
        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $direction = (int) $validated['position'] < $possessionCondition->position ? 'up' : 'down';

        $possessionCondition = new UpdatePossessionConditionPosition(
            user: $request->user(),
            possessionCondition: $possessionCondition,
            position: (int) $validated['position'],
        )->execute();

        return redirect()->route('settings.possessionConditions.index')
            ->with('status', __('“:name” is moved to position :position.', ['name' => $possessionCondition->name, 'position' => $possessionCondition->position]))
            ->with('moved', $possessionCondition->uuid)
            ->with('direction', $direction);
    }
}
