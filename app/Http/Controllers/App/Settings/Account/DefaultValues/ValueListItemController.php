<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\DefaultValues;

use App\Actions\CreateValueListItem;
use App\Actions\DestroyValueListItem;
use App\Actions\UpdateValueListItem;
use App\Exceptions\CannotDeleteAValueInUse;
use App\Http\Controllers\Controller;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValueListItemController extends Controller
{
    public function create(Request $request, ValueList $valueList): RedirectResponse
    {
        $validated = $request->validateWithBag("newValue{$valueList->id}", [
            'label' => ['required', 'string', 'max:255'],
        ]);

        new CreateValueListItem(
            user: $request->user(),
            valueList: $valueList,
            label: $validated['label'],
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is added.'))
            ->with('status_description', __('It goes at the end of the list, where you can move it from.'));
    }

    public function update(Request $request, ValueListItem $valueListItem): RedirectResponse
    {
        $validated = $request->validateWithBag("value{$valueListItem->id}", [
            'label' => ['required', 'string', 'max:255'],
        ]);

        new UpdateValueListItem(
            user: $request->user(),
            item: $valueListItem,
            label: $validated['label'],
            description: $valueListItem->description,
            colour: $valueListItem->colour,
            icon: $valueListItem->icon,
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is renamed.'))
            ->with('status_description', __('Everything offering it says the new name straight away.'));
    }

    public function destroy(Request $request, ValueListItem $valueListItem): RedirectResponse
    {
        try {
            new DestroyValueListItem(
                user: $request->user(),
                item: $valueListItem,
            )->execute();
        } catch (CannotDeleteAValueInUse) {
            return redirect()->route('settings.valueLists.index')
                ->with('error', __('The value is in use and stays.'))
                ->with('error_description', __('Stop offering it instead, and everything that already has it keeps it.'));
        }

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is deleted.'))
            ->with('status_description', __('Nothing was using it.'));
    }
}
