<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\UpdatePossessionFieldGroupPosition;
use App\Http\Controllers\Controller;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionFieldGroupPositionController extends Controller
{
    public function update(Request $request, PossessionType $possessionType, PossessionFieldGroup $possessionFieldGroup): RedirectResponse
    {
        abort_unless($possessionFieldGroup->possession_type_id === $possessionType->id, 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $direction = (int) $validated['position'] < $possessionFieldGroup->position ? 'up' : 'down';

        new UpdatePossessionFieldGroupPosition(
            user: $request->user(),
            group: $possessionFieldGroup,
            position: (int) $validated['position'],
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('“:name” is moved to position :position.', ['name' => $possessionFieldGroup->name, 'position' => $validated['position']]))
            ->with('moved', "group-{$possessionFieldGroup->uuid}")
            ->with('direction', $direction);
    }
}
