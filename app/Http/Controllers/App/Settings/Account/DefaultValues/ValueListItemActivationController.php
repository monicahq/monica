<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\DefaultValues;

use App\Actions\DisableValueListItem;
use App\Actions\EnableValueListItem;
use App\Http\Controllers\Controller;
use App\Models\ValueListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Whether a value is still active, which is the `is_active` column and nothing
 * more. This is what a value in use gets instead of a deletion, since
 * `DestroyValueListItem` refuses one that records already point at: the value
 * stops being offered, keeps its place in the list, and every record that has
 * it keeps it.
 *
 * The flag is a resource of its own rather than two routes reading `enable`
 * and `disable`, so the screen stays on the CRUD words the project keeps to.
 */
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
