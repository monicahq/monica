<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\CreatePossessionFieldGroup;
use App\Actions\DestroyPossessionFieldGroup;
use App\Actions\UpdatePossessionFieldGroup;
use App\Http\Controllers\Controller;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PossessionFieldGroupController extends Controller
{
    public function create(Request $request, PossessionType $possessionType): RedirectResponse
    {
        new CreatePossessionFieldGroup(
            user: $request->user(),
            possessionType: $possessionType,
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('The group is added.'));
    }

    public function update(Request $request, PossessionType $possessionType, PossessionFieldGroup $possessionFieldGroup): RedirectResponse
    {
        abort_unless($possessionFieldGroup->possession_type_id === $possessionType->id, 404);

        $validated = $request->validateWithBag("group{$possessionFieldGroup->uuid}", [
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            new UpdatePossessionFieldGroup(
                user: $request->user(),
                group: $possessionFieldGroup,
                name: $validated['name'],
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag("group{$possessionFieldGroup->uuid}");
        }

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('Saved.'));
    }

    public function destroy(Request $request, PossessionType $possessionType, PossessionFieldGroup $possessionFieldGroup): RedirectResponse
    {
        abort_unless($possessionFieldGroup->possession_type_id === $possessionType->id, 404);

        new DestroyPossessionFieldGroup(
            user: $request->user(),
            group: $possessionFieldGroup,
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('The group is removed. Its fields now stand alone.'))
            ->with('focus', 'add-group');
    }
}
