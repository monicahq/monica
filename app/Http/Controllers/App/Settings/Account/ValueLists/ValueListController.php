<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ValueLists;

use App\Actions\CreateValueList;
use App\Actions\DestroyValueList;
use App\Actions\UpdateValueList;
use App\Http\Controllers\Controller;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\ValueLists\ValueListsViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ValueListController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.value-lists.index', [
            'viewModel' => new ValueListsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newList', [
            'name' => ['required', 'string', 'max:255'],
        ]);

        new CreateValueList(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The list is added.'))
            ->with('status_description', __('It is empty until you put something in it.'));
    }

    public function update(Request $request, ValueList $valueList): RedirectResponse
    {
        $validated = $request->validateWithBag("list{$valueList->id}", [
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdateValueList(
            user: $request->user(),
            valueList: $valueList,
            name: $validated['name'],
            description: $valueList->description,
            allowsUserValues: $valueList->allows_user_values,
        )->execute();

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The list is renamed.'))
            ->with('status_description', __('It now reads the same in every language.'));
    }

    public function destroy(Request $request, ValueList $valueList): RedirectResponse
    {
        try {
            new DestroyValueList(
                user: $request->user(),
                valueList: $valueList,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.valueLists.index')
                ->with('error', $e->getMessage())
                ->with('error_description', __('The application itself reads from this list. Empty it instead, and it offers nothing.'));
        }

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The list is deleted.'))
            ->with('status_description', __('The values it held were deleted with it.'));
    }
}
