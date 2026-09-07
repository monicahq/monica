<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ValueLists;

use App\Actions\UpdateValueListItemPosition;
use App\Http\Controllers\Controller;
use App\Models\ValueListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValueListItemPositionController extends Controller
{
    public function update(Request $request, ValueListItem $valueListItem): RedirectResponse
    {
        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateValueListItemPosition(
            user: $request->user(),
            item: $valueListItem,
            position: $request->integer('position'),
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The order is saved.'))
            ->with('status_description', __('The values are offered in the order you put them in.'));
    }
}
