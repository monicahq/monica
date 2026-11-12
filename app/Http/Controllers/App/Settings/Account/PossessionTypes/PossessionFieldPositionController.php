<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\UpdatePossessionFieldPosition;
use App\Http\Controllers\Controller;
use App\Models\PossessionField;
use App\Models\PossessionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionFieldPositionController extends Controller
{
    public function update(Request $request, PossessionType $possessionType, PossessionField $possessionField): RedirectResponse
    {
        abort_unless($possessionField->possession_type_id === $possessionType->id, 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $direction = (int) $validated['position'] < $possessionField->position ? 'up' : 'down';

        new UpdatePossessionFieldPosition(
            user: $request->user(),
            field: $possessionField,
            position: (int) $validated['position'],
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('“:name” is moved to position :position.', ['name' => $possessionField->name, 'position' => $validated['position']]))
            ->with('moved', "field-{$possessionField->uuid}")
            ->with('direction', $direction);
    }
}
