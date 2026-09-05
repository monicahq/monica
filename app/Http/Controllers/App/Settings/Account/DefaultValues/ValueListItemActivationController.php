<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\DefaultValues;

use App\Actions\DisableValueListItem;
use App\Actions\EnableValueListItem;
use App\Http\Controllers\Controller;
use App\Models\ValueListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValueListItemActivationController extends Controller
{
    public function update(Request $request, ValueListItem $valueListItem): RedirectResponse
    {
        $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if ($request->boolean('active')) {
            new EnableValueListItem(
                user: $request->user(),
                item: $valueListItem,
            )->execute();

            return redirect()->route('settings.valueLists.index')
                ->with('status', __('The value is offered again.'))
                ->with('status_description', __('It went back where it was in the list.'));
        }

        new DisableValueListItem(
            user: $request->user(),
            item: $valueListItem,
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is no longer offered.'))
            ->with('status_description', __('Everything that already has it keeps it.'));
    }
}
