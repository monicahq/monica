<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\ValueLists;

use App\Actions\CreateValueListItem;
use App\Actions\DestroyValueListItem;
use App\Actions\UpdateValueListItem;
use App\Helpers\CountryCodes;
use App\Http\Controllers\Controller;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\ViewModels\Settings\Account\ValueLists\ValueListsViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ValueListItemController extends Controller
{
    public function create(Request $request, ValueList $valueList): RedirectResponse
    {
        $request->merge(['country_code' => mb_strtoupper($request->string('country_code')->trim()->value()) ?: null]);

        $bag = "newValue{$valueList->id}";
        $validated = $request->validateWithBag($bag, [
            'label' => ['nullable', 'required_without:country_code', 'string', 'max:255'],
            'country_code' => ['nullable', 'required_without:label', 'string', 'size:2', Rule::in(CountryCodes::known())],
        ]);

        try {
            new CreateValueListItem(
                user: $request->user(),
                valueList: $valueList,
                label: $validated['label'] ?? null,
                countryCode: $validated['country_code'] ?? null,
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag($bag);
        }

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is added.'))
            ->with('status_description', __('It goes at the end of the list, where you can move it from.'));
    }

    public function edit(Request $request, ValueListItem $valueListItem): View
    {
        abort_unless($valueListItem->account_id === $request->user()->account_id, 404);

        return view('app.settings.account.value-lists.index', [
            'viewModel' => new ValueListsViewModel(
                user: $request->user(),
                editingItem: $valueListItem,
            ),
        ]);
    }

    public function update(Request $request, ValueListItem $valueListItem): RedirectResponse
    {
        $request->merge(['country_code' => mb_strtoupper($request->string('country_code')->trim()->value()) ?: null]);

        $bag = "value{$valueListItem->id}";
        $validated = $request->validateWithBag($bag, [
            'label' => ['nullable', 'required_without:country_code', 'string', 'max:255'],
            'country_code' => ['nullable', 'required_without:label', 'string', 'size:2', Rule::in(CountryCodes::known())],
        ]);

        try {
            new UpdateValueListItem(
                user: $request->user(),
                item: $valueListItem,
                label: $validated['label'] ?? null,
                description: $valueListItem->description,
                colour: $valueListItem->colour,
                icon: $valueListItem->icon,
                countryCode: $validated['country_code'] ?? null,
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag($bag);
        }

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
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.valueLists.index')
                ->with('error', $e->getMessage())
                ->with('error_description', __('Stop offering it instead, and everything that already has it keeps it.'));
        }

        return redirect()->route('settings.valueLists.index')
            ->with('status', __('The value is deleted.'))
            ->with('status_description', __('Nothing was using it.'));
    }
}
